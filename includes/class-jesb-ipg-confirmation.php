<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class JESB_IPG_Confirmation {
    public static function init() {
        add_action( 'rest_api_init', static function () {
            register_rest_route( 'jesb-infinitepay/v1', '/webhook', array(
                'methods' => 'POST',
                'callback' => array( __CLASS__, 'webhook' ),
                'permission_callback' => '__return_true', // Authentication occurs using the authoritative payment_check API.
            ) );
        } );
        add_filter( 'woocommerce_payment_complete_order_status', array( __CLASS__, 'completion_status' ), 20, 3 );
    }
    public static function webhook( WP_REST_Request $request ) {
        if ( strlen( $request->get_body() ) > 16384 ) {
            return new WP_Error( 'jesb_ipg_size', __( 'Conteúdo inválido.', 'jesb-infinitepay-gateway' ), array( 'status' => 413 ) );
        }
        $data = $request->get_json_params();
        if ( ! is_array( $data ) ) { return new WP_Error( 'jesb_ipg_json', __( 'Conteúdo inválido.', 'jesb-infinitepay-gateway' ), array( 'status' => 400 ) ); }
        $nsu = isset( $data['order_nsu'] ) && is_string( $data['order_nsu'] ) ? $data['order_nsu'] : '';
        if ( ! preg_match( '/^jig-([1-9][0-9]{0,17})-[a-zA-Z0-9]{24}$/D', $nsu, $matches ) ) {
            return new WP_Error( 'jesb_ipg_nsu', __( 'Referência inválida.', 'jesb-infinitepay-gateway' ), array( 'status' => 400 ) );
        }
        $order = wc_get_order( (int) $matches[1] );
        if ( ! $order || 'jesb_infinitepay' !== $order->get_payment_method() || ! hash_equals( (string) $order->get_meta( '_jesb_ipg_order_nsu', true ), $nsu ) ) {
            return new WP_Error( 'jesb_ipg_order', __( 'Pedido não localizado.', 'jesb-infinitepay-gateway' ), array( 'status' => 404 ) );
        }
        $result = self::verify( $order, array(
            'order_nsu' => $nsu,
            'transaction_nsu' => $data['transaction_nsu'] ?? '',
            'slug' => $data['invoice_slug'] ?? ( $data['slug'] ?? '' ),
        ) );
        if ( is_wp_error( $result ) ) {
            JESB_IPG_API::log( 'Webhook aguardando verificação válida.', array( 'order_id' => $order->get_id(), 'reason' => $result->get_error_code() ), 'warning' );
            return new WP_Error( 'jesb_ipg_not_confirmed', __( 'Pagamento ainda não confirmado.', 'jesb-infinitepay-gateway' ), array( 'status' => 503 ) );
        }
        return rest_ensure_response( array( 'ok' => true, 'paid' => (bool) $result ) );
    }
    public static function verify( $order, $params ) {
        if ( ! $order instanceof WC_Order || 'jesb_infinitepay' !== $order->get_payment_method() ) {
            return new WP_Error( 'jesb_ipg_order', 'Invalid order' );
        }
        $nsu = (string) ( $params['order_nsu'] ?? '' );
        $txn = $params['transaction_nsu'] ?? '';
        $slug = $params['slug'] ?? '';
        $expected_nsu = (string) $order->get_meta( '_jesb_ipg_order_nsu', true );
        if ( ! is_string( $txn ) || ! is_string( $slug ) || ! $nsu || ! hash_equals( $expected_nsu, $nsu ) ||
            ! preg_match( '/^[a-zA-Z0-9_.:-]{1,160}$/D', $txn ) ||
            ! preg_match( '/^[a-zA-Z0-9_.:-]{1,160}$/D', $slug ) ) {
            return new WP_Error( 'jesb_ipg_params', 'Invalid payment identifiers' );
        }
        if ( 'BRL' !== $order->get_currency() ) { return new WP_Error( 'jesb_ipg_currency', 'Currency mismatch' ); }
        $existing = (string) $order->get_meta( '_jesb_ipg_confirmed_transaction', true );
        if ( $existing && ! hash_equals( $existing, $txn ) ) {
            return new WP_Error( 'jesb_ipg_transaction_conflict', 'Transaction conflict' );
        }
        // Avoid duplicate concurrent confirmations. Atomic add_option works across web workers.
        $lock_key = 'jesb_ipg_lock_' . $order->get_id();
        $locked = add_option( $lock_key, time(), '', 'no' );
        if ( ! $locked ) {
            $since = (int) get_option( $lock_key, 0 );
            if ( $since && $since < time() - 60 ) {
                delete_option( $lock_key );
                $locked = add_option( $lock_key, time(), '', 'no' );
            }
        }
        if ( ! $locked ) { return new WP_Error( 'jesb_ipg_busy', 'Payment confirmation in progress' ); }
        try {
            $result = JESB_IPG_API::request( 'payment_check', array(
                'handle' => JESB_IPG_API::handle(),
                'order_nsu' => $nsu,
                'transaction_nsu' => $txn,
                'slug' => $slug,
            ) );
            if ( is_wp_error( $result ) ) { return $result; }
            if ( true !== ( $result['success'] ?? null ) || true !== ( $result['paid'] ?? null ) ) {
                return new WP_Error( 'jesb_ipg_unpaid', 'Payment not verified' );
            }
            $expected = (int) $order->get_meta( '_jesb_ipg_amount_cents', true );
            if ( $expected < 100 || $expected !== JESB_IPG_API::cents( $order->get_total() ) ||
                ! isset( $result['amount'] ) || ! is_numeric( $result['amount'] ) || (int) $result['amount'] !== $expected ) {
                JESB_IPG_API::log( 'Divergência de valor detectada.', array( 'order_id' => $order->get_id() ), 'error' );
                return new WP_Error( 'jesb_ipg_amount', 'Amount mismatch' );
            }
            // Load fresh order after remote API call to avoid stale payment state.
            $order = wc_get_order( $order->get_id() );
            if ( ! $order || 'jesb_infinitepay' !== $order->get_payment_method() ) { return new WP_Error( 'jesb_ipg_changed', 'Order changed' ); }
            $existing = (string) $order->get_meta( '_jesb_ipg_confirmed_transaction', true );
            if ( $existing && ! hash_equals( $existing, $txn ) ) { return new WP_Error( 'jesb_ipg_conflict', 'Transaction conflict' ); }
            if ( $order->is_paid() && ! $existing ) {
                // Do not overwrite a payment confirmed by another system/operator.
                return new WP_Error( 'jesb_ipg_previously_paid', 'Order already paid without verifiable transaction' );
            }
            if ( ! $order->is_paid() && ! $existing ) {
                if ( ! $order->has_status( array( 'pending', 'failed', 'on-hold' ) ) ) {
                    return new WP_Error( 'jesb_ipg_status', 'Unexpected order status' );
                }
                $order->update_meta_data( '_jesb_ipg_confirmed_transaction', $txn );
                $order->update_meta_data( '_jesb_ipg_payment_method', sanitize_key( (string) ( $result['capture_method'] ?? '' ) ) );
                $order->update_meta_data( '_jesb_ipg_installments', absint( $result['installments'] ?? 0 ) );
                $order->save();
                $order->payment_complete( $txn );
                JESB_IPG_API::log( 'Pagamento aprovado após consulta oficial.', array( 'order_id' => $order->get_id() ) );
            }
            return true;
        } finally {
            delete_option( $lock_key );
        }
    }
    public static function completion_status( $status, $order_id, $order ) {
        if ( ! $order instanceof WC_Order || 'jesb_infinitepay' !== $order->get_payment_method() ) { return $status; }
        $items = $order->get_items( 'line_item' );
        if ( ! $items ) { return 'processing'; }
        foreach ( $items as $item ) {
            if ( ! $item instanceof WC_Order_Item_Product ) { return 'processing'; }
            $product = $item->get_product();
            if ( ! $product || ! $product->is_virtual() || ! $product->is_downloadable() ) { return 'processing'; }
        }
        return 'completed';
    }
}
