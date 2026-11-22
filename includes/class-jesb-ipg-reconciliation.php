<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Retry only transactions for which InfinitePay supplied checkable identifiers. */
final class JESB_IPG_Reconciliation {
    const HOOK = 'jesb_ipg_verify_transaction';
    const GROUP = 'jesb-infinitepay';
    public static function init() {
        add_action( self::HOOK, array( __CLASS__, 'run' ), 10, 3 );
    }
    public static function queue( WC_Order $order, $nsu, $transaction, $slug, $attempt = 0 ) {
        if ( ! function_exists( 'as_schedule_single_action' ) ) { return false; }
        if ( $attempt >= 6 || $order->is_paid() ) { return false; }
        if ( ! JESB_IPG_Confirmation::valid_identifiers( $nsu, $transaction, $slug ) ) { return false; }
        if ( ! JESB_IPG_Attempts::find( $order, $nsu ) ) { return false; }
        $args = array( (int) $order->get_id(), (string) $nsu, (int) $attempt );
        $seconds = min( 3600, 60 * ( 2 ** $attempt ) );
        // Store identifiers in WC order meta rather than in scheduler arguments / dashboards.
        $order->update_meta_data( '_jesb_ipg_reconcile_' . hash( 'sha256', $nsu ), array(
            'transaction' => $transaction,
            'slug' => $slug,
            'scheduled_at' => time(),
        ) );
        $order->save();
        return (bool) as_schedule_single_action( time() + $seconds, self::HOOK, $args, self::GROUP, true );
    }
    public static function run( $order_id, $nsu, $attempt ) {
        $order = wc_get_order( absint( $order_id ) );
        if ( ! $order || 'jesb_infinitepay' !== $order->get_payment_method() || $order->is_paid() ) { return; }
        $meta_key = '_jesb_ipg_reconcile_' . hash( 'sha256', (string) $nsu );
        $identifiers = $order->get_meta( $meta_key, true );
        if ( ! is_array( $identifiers ) || ! isset( $identifiers['transaction'], $identifiers['slug'] ) ) { return; }
        $params = array( 'order_nsu' => $nsu, 'transaction_nsu' => $identifiers['transaction'], 'slug' => $identifiers['slug'] );
        $verified = JESB_IPG_Confirmation::verify( $order, $params );
        if ( true === $verified ) {
            $order = wc_get_order( $order_id );
            $order->delete_meta_data( $meta_key );
            $order->save();
            return;
        }
        if ( is_wp_error( $verified ) && in_array( $verified->get_error_code(), array( 'jesb_ipg_network', 'jesb_ipg_http', 'jesb_ipg_busy', 'jesb_ipg_unpaid' ), true ) ) {
            if ( self::queue( wc_get_order( $order_id ), $nsu, $identifiers['transaction'], $identifiers['slug'], absint( $attempt ) + 1 ) ) { return; }
        }
        JESB_IPG_API::log( 'Conciliação exige verificação manual.', array( 'order_id' => $order_id, 'reason' => 'reconcile_exhausted' ), 'warning' );
        $order = wc_get_order( $order_id );
        $order->update_meta_data( '_jesb_ipg_attention_required', 'yes' );
        $order->save();
    }
}
