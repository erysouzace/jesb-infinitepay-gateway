<?php
/**
 * Plugin Name: JESB InfinitePay Gateway
 * Plugin URI: https://jesbdigital.com.br/
 * Description: Checkout hospedado InfinitePay para WooCommerce, confirmação validada e suporte a blocos/HPOS.
 * Version: 1.1.4
 * Requires Plugins: woocommerce
 * Requires PHP: 8.2
 * Requires at least: 6.4
 * Author: JESB DIGITAL
 * Text Domain: jesb-infinitepay-gateway
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'JESB_IPG_VERSION', '1.1.4' );
define( 'JESB_IPG_PATH', plugin_dir_path( __FILE__ ) );
define( 'JESB_IPG_URL', plugin_dir_url( __FILE__ ) );

add_action( 'before_woocommerce_init', static function () {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
    }
} );

add_action( 'plugins_loaded', static function () {
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
    require_once JESB_IPG_PATH . 'includes/class-jesb-ipg-api.php';
    require_once JESB_IPG_PATH . 'includes/class-jesb-ipg-checkout-data.php';
    require_once JESB_IPG_PATH . 'includes/class-jesb-ipg-attempts.php';
    require_once JESB_IPG_PATH . 'includes/class-jesb-ipg-confirmation.php';
    require_once JESB_IPG_PATH . 'includes/class-jesb-ipg-reconciliation.php';
    require_once JESB_IPG_PATH . 'includes/class-jesb-ipg-gateway.php';
    add_filter( 'woocommerce_payment_gateways', static function ( $gateways ) {
        $gateways[] = 'JESB_IPG_Gateway';
        return $gateways;
    } );
    JESB_IPG_Confirmation::init();
    JESB_IPG_Reconciliation::init();
    require_once JESB_IPG_PATH . 'includes/class-jesb-ipg-admin.php';
    JESB_IPG_Admin::init();
}, 20 );

// Register the Blocks integration as soon as WooCommerce initializes Blocks.
// Do not attach this action from a late plugins_loaded handler: that may miss the event.
add_action( 'woocommerce_blocks_loaded', static function () {
    if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }
    require_once JESB_IPG_PATH . 'includes/class-jesb-ipg-blocks.php';
    add_action( 'woocommerce_blocks_payment_method_type_registration', static function ( $registry ) {
        $registry->register( new JESB_IPG_Blocks() );
    }, 5 );
}, 5 );

// Make configuration failures visible to an administrator, never to customers.
add_action( 'admin_notices', static function () {
    if ( ! current_user_can( 'manage_woocommerce' ) || ! function_exists( 'get_current_screen' ) ) { return; }
    $screen = get_current_screen();
    if ( ! $screen || 'woocommerce_page_wc-settings' !== $screen->id ) { return; }
    if ( ! class_exists( 'JESB_IPG_API' ) ) { return; }
    $settings = JESB_IPG_API::settings();
    if ( 'yes' !== ( $settings['enabled'] ?? 'no' ) ) { return; }
    $problems = JESB_IPG_API::configuration_issues();
    if ( ! $problems ) { return; }
    echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'JESB InfinitePay Gateway: indisponível no checkout.', 'jesb-infinitepay-gateway' ) . '</strong> ' . esc_html( implode( ' ', $problems ) ) . '</p></div>';
} );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), static function ( $links ) {
    if ( class_exists( 'WooCommerce' ) ) {
        array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=jesb_infinitepay' ) ) . '">' . esc_html__( 'Configurações', 'jesb-infinitepay-gateway' ) . '</a>' );
    }
    return $links;
} );
