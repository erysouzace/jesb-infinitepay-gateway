<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class JESB_IPG_Gateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->id                 = 'jesb_infinitepay';
        $this->method_title       = __( 'JESB InfinitePay Gateway', 'jesb-infinitepay-gateway' );
        $this->method_description = __( 'Pagamento hospedado com Pix ou cartão. A aprovação é verificada diretamente na InfinitePay.', 'jesb-infinitepay-gateway' );
        $this->has_fields         = false;
        $this->supports           = array( 'products' );
        $this->init_form_fields();
        $this->init_settings();
        $this->enabled     = $this->get_option( 'enabled', 'no' );
        $this->title       = $this->get_option( 'title', __( 'Pix ou cartão — InfinitePay', 'jesb-infinitepay-gateway' ) );
        $this->description = $this->get_option( 'description', __( 'Pague com segurança na página da InfinitePay.', 'jesb-infinitepay-gateway' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'verify_on_return' ), 5 );
    }
    public function is_available() {
        return parent::is_available() && 'BRL' === get_woocommerce_currency() && '' !== JESB_IPG_API::handle() && JESB_IPG_API::transport_ready();
    }
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array( 'title' => __( 'Ativar/Desativar', 'jesb-infinitepay-gateway' ), 'type' => 'checkbox', 'label' => __( 'Ativar InfinitePay no checkout', 'jesb-infinitepay-gateway' ), 'default' => 'no' ),
            'handle' => array( 'title' => __( 'InfiniteTag', 'jesb-infinitepay-gateway' ), 'type' => 'text', 'description' => __( 'Identificador da conta, sem o símbolo $.', 'jesb-infinitepay-gateway' ), 'default' => '', 'desc_tip' => true ),
            'title' => array( 'title' => __( 'Título exibido no checkout', 'jesb-infinitepay-gateway' ), 'type' => 'text', 'default' => __( 'Pix ou cartão — InfinitePay', 'jesb-infinitepay-gateway' ) ),
            'description' => array( 'title' => __( 'Descrição exibida no checkout', 'jesb-infinitepay-gateway' ), 'type' => 'textarea', 'default' => __( 'Pague com segurança na página da InfinitePay.', 'jesb-infinitepay-gateway' ) ),
            'debug' => array( 'title' => __( 'Diagnóstico', 'jesb-infinitepay-gateway' ), 'type' => 'checkbox', 'label' => __( 'Registrar eventos técnicos sem informações pessoais ou segredos', 'jesb-infinitepay-gateway' ), 'default' => 'no' ),
            'diagnostic_info' => array( 'title' => __( 'Diagnóstico de disponibilidade', 'jesb-infinitepay-gateway' ), 'type' => 'title', 'description' => JESB_IPG_API::configuration_issues() ? '<strong style="color:#b32d2e">' . esc_html( implode( ' ', JESB_IPG_API::configuration_issues() ) ) . '</strong>' : '<strong style="color:#008a20">' . esc_html__( 'Configuração básica válida: InfiniteTag, BRL e URLs HTTPS.', 'jesb-infinitepay-gateway' ) . '</strong><p>' . esc_html__( 'A confirmação de compatibilidade com Blocks e API exige testes reais.', 'jesb-infinitepay-gateway' ) . '</p>' ),
            'webhook_info' => array( 'title' => __( 'URL do webhook', 'jesb-infinitepay-gateway' ), 'type' => 'title', 'description' => '<code>' . esc_html( rest_url( 'jesb-infinitepay/v1/webhook' ) ) . '</code><p>' . esc_html__( 'Esta URL é enviada automaticamente à InfinitePay em cada link.', 'jesb-infinitepay-gateway' ) . '</p>' ),
            'return_info' => array( 'title' => __( 'URL de retorno', 'jesb-infinitepay-gateway' ), 'type' => 'title', 'description' => '<code>' . esc_html( wc_get_checkout_url() ) . '</code><p>' . esc_html__( 'O retorno exato é gerado por pedido e já é transmitido à API. Não é necessário cadastrá-lo manualmente.', 'jesb-infinitepay-gateway' ) . '</p>' ),
        );
    }
    public function validate_handle_field( $key, $value ) {
        $value = ltrim( trim( sanitize_text_field( (string) $value ) ), '$' );
        if ( '' !== $value && ! preg_match( '/^[a-zA-Z0-9_.-]{2,100}$/D', $value ) ) {
            WC_Admin_Settings::add_error( __( 'InfiniteTag inválida. Utilize apenas letras, números, ponto, hífen ou sublinhado.', 'jesb-infinitepay-gateway' ) );
            return $this->get_option( 'handle', '' );
        }
        return $value;
    }
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order || $order->get_payment_method() !== $this->id ) {
            wc_add_notice( __( 'Pedido inválido para esta forma de pagamento.', 'jesb-infinitepay-gateway' ), 'error' );
            return array( 'result' => 'failure' );
        }
        if ( $order->is_paid() ) {
            return array( 'result' => 'success', 'redirect' => $this->get_return_url( $order ) );
        }
        if ( ! JESB_IPG_API::transport_ready() || 'BRL' !== $order->get_currency() || '' === JESB_IPG_API::handle() ) {
            wc_add_notice( __( 'O pagamento requer HTTPS, reais brasileiros e uma InfiniteTag configurada.', 'jesb-infinitepay-gateway' ), 'error' );
            return array( 'result' => 'failure' );
        }
        $cents = JESB_IPG_API::cents( $order->get_total() );
        if ( $cents < 100 ) {
            wc_add_notice( __( 'O valor mínimo da InfinitePay é R$ 1,00.', 'jesb-infinitepay-gateway' ), 'error' );
            return array( 'result' => 'failure' );
        }
        // Reuse the same payment URL to avoid creating multiple payable invoices.
        $old_url = $order->get_meta( '_jesb_ipg_payment_url', true );
        $old_total = (int) $order->get_meta( '_jesb_ipg_amount_cents', true );
        if ( JESB_IPG_API::is_valid_payment_url( $old_url ) && $old_total === $cents ) {
            return array( 'result' => 'success', 'redirect' => $old_url );
        }
        // If the order total changed, do not silently create a competing payable link.
        if ( $old_url ) {
            wc_add_notice( __( 'O valor do pedido mudou após a geração do pagamento. Contate a loja para regularizar a cobrança.', 'jesb-infinitepay-gateway' ), 'error' );
            return array( 'result' => 'failure' );
        }
        $nsu = (string) $order->get_meta( '_jesb_ipg_order_nsu', true );
        if ( ! $nsu ) {
            $nsu = 'jig-' . $order->get_id() . '-' . wp_generate_password( 24, false, false );
            $order->update_meta_data( '_jesb_ipg_order_nsu', $nsu );
            $order->update_meta_data( '_jesb_ipg_amount_cents', $cents );
            $order->save();
        }
        $payload = array(
            'handle' => JESB_IPG_API::handle(),
            'items' => JESB_IPG_Checkout_Data::items( $order ),
            'order_nsu' => $nsu,
            'redirect_url' => $this->get_return_url( $order ),
            'webhook_url' => rest_url( 'jesb-infinitepay/v1/webhook' ),
        );
        // Only share the details necessary to prefill the hosted checkout.
        $customer = JESB_IPG_Checkout_Data::customer( $order );
        if ( $customer ) { $payload['customer'] = $customer; }
        $address = JESB_IPG_Checkout_Data::address( $order );
        if ( $address ) { $payload['address'] = $address; }
        // With a server-side API key, prevent paying the same link twice and set a finite lifetime.
        if ( defined( 'JESB_INFINITEPAY_API_KEY' ) && '' !== (string) JESB_INFINITEPAY_API_KEY ) {
            $payload['options'] = array(
                'single_payment' => true,
                'valid_until' => gmdate( 'Y-m-d\TH:i:s\Z', time() + DAY_IN_SECONDS ),
                'require_delivery_address' => JESB_IPG_Checkout_Data::has_physical_items( $order ),
            );
        }
        $result = JESB_IPG_API::request( 'links', $payload );
        if ( is_wp_error( $result ) ) {
            wc_add_notice( $result->get_error_message(), 'error' );
            return array( 'result' => 'failure' );
        }
        $url = $result['url'] ?? '';
        if ( ! JESB_IPG_API::is_valid_payment_url( $url ) ) {
            JESB_IPG_API::log( 'Link de pagamento inválido ou não reconhecido.', array( 'order_id' => $order_id ), 'error' );
            wc_add_notice( __( 'A InfinitePay não retornou um endereço seguro de pagamento.', 'jesb-infinitepay-gateway' ), 'error' );
            return array( 'result' => 'failure' );
        }
        $order->update_meta_data( '_jesb_ipg_payment_url', esc_url_raw( $url ) );
        $order->save();
        if ( 'pending' !== $order->get_status() ) { $order->update_status( 'pending' ); }
        JESB_IPG_API::log( 'Link de pagamento gerado.', array( 'order_id' => $order_id ) );
        return array( 'result' => 'success', 'redirect' => $url );
    }
    public function verify_on_return( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order || $order->get_payment_method() !== $this->id ) { return; }
        if ( ! isset( $_GET['key'] ) || ! hash_equals( $order->get_order_key(), sanitize_text_field( wp_unslash( $_GET['key'] ) ) ) ) { return; }
        if ( ! isset( $_GET['transaction_nsu'], $_GET['slug'], $_GET['order_nsu'] ) ) { return; }
        $params = array(
            'transaction_nsu' => sanitize_text_field( wp_unslash( $_GET['transaction_nsu'] ) ),
            'slug' => sanitize_text_field( wp_unslash( $_GET['slug'] ) ),
            'order_nsu' => sanitize_text_field( wp_unslash( $_GET['order_nsu'] ) ),
        );
        $result = JESB_IPG_Confirmation::verify( $order, $params );
        if ( is_wp_error( $result ) ) {
            echo '<p>' . esc_html__( 'A confirmação está em análise. Se o pagamento foi realizado, não pague novamente. A loja poderá verificar sua transação.', 'jesb-infinitepay-gateway' ) . '</p>';
        }
    }
}
