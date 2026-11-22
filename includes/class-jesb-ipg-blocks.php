<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class JESB_IPG_Blocks extends \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {
    protected $name = 'jesb_infinitepay';
    public function initialize() {
        $this->settings = (array) get_option( 'woocommerce_jesb_infinitepay_settings', array() );
    }
    public function is_active() {
        return 'yes' === $this->get_setting( 'enabled', 'no' ) && '' !== JESB_IPG_API::handle() && 'BRL' === get_woocommerce_currency() && JESB_IPG_API::transport_ready();
    }
    public function get_payment_method_script_handles() {
        wp_register_script(
            'jesb-infinitepay-blocks',
            JESB_IPG_URL . 'assets/js/blocks.js',
            array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n', 'wp-html-entities' ),
            JESB_IPG_VERSION,
            true
        );
        return array( 'jesb-infinitepay-blocks' );
    }
    public function get_payment_method_script_handles_for_admin() {
        return $this->get_payment_method_script_handles();
    }
    public function get_payment_method_data() {
        return array(
            'title' => $this->get_setting( 'title', __( 'Pix ou cartão — InfinitePay', 'jesb-infinitepay-gateway' ) ),
            'description' => $this->get_setting( 'description', __( 'Pague com segurança na página da InfinitePay.', 'jesb-infinitepay-gateway' ) ),
            'supports' => array( 'products' ),
        );
    }
}
