<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Read-only financial overview based on WooCommerce order data. */
final class JESB_IPG_Admin {
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ), 30 );
    }
    public static function menu() {
        add_submenu_page(
            'woocommerce',
            __( 'JESB InfinitePay — Financeiro', 'jesb-infinitepay-gateway' ),
            __( 'JESB InfinitePay', 'jesb-infinitepay-gateway' ),
            'manage_woocommerce',
            'jesb-infinitepay-financeiro',
            array( __CLASS__, 'render' )
        );
    }
    public static function render() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( esc_html__( 'Acesso negado.', 'jesb-infinitepay-gateway' ) ); }
        if ( ! function_exists( 'wc_get_orders' ) ) { return; }
        // Fixed, bounded query; no raw SQL and HPOS-compatible WooCommerce CRUD.
        $orders = wc_get_orders( array(
            'limit'   => 100,
            'orderby' => 'date',
            'order'   => 'DESC',
            'return'  => 'objects',
        ) );
        $pending = 0;
        $paid = 0;
        $approved = 0;
        $records = array();
        foreach ( $orders as $order ) {
            if ( ! $order instanceof WC_Order || 'jesb_infinitepay' !== $order->get_payment_method() ) { continue; }
            $records[] = $order;
            if ( $order->is_paid() ) {
                ++$paid;
                $approved += (float) $order->get_total();
            } elseif ( $order->has_status( array( 'pending', 'on-hold', 'failed' ) ) ) {
                ++$pending;
            }
        }
        $settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=jesb_infinitepay' );
        echo '<div class="wrap"><h1>' . esc_html__( 'JESB InfinitePay — Financeiro', 'jesb-infinitepay-gateway' ) . '</h1>';
        echo '<p>' . esc_html__( 'Visão operacional: amostra limitada aos 100 pedidos mais recentes da loja. Não é um extrato bancário nem um demonstrativo contábil completo.', 'jesb-infinitepay-gateway' ) . '</p>';
        echo '<div style="display:flex;flex-wrap:wrap;gap:12px;margin:18px 0">';
        $cards = array(
            __( 'Pagamentos aprovados na amostra', 'jesb-infinitepay-gateway' ) => (string) $paid,
            __( 'Pendentes na amostra', 'jesb-infinitepay-gateway' ) => (string) $pending,
            __( 'Bruto aprovado na amostra', 'jesb-infinitepay-gateway' ) => wp_strip_all_tags( wc_price( $approved ) ),
            __( 'Taxas reais da InfinitePay', 'jesb-infinitepay-gateway' ) => __( 'Não fornecidas pela API', 'jesb-infinitepay-gateway' ),
        );
        foreach ( $cards as $heading => $value ) {
            echo '<div style="min-width:190px;flex:1;max-width:320px;background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:18px">';
            echo '<div style="color:#50575e">' . esc_html( $heading ) . '</div><div style="font-size:23px;font-weight:700;margin-top:8px">' . esc_html( $value ) . '</div></div>';
        }
        echo '</div><h2>' . esc_html__( 'Transações recentes', 'jesb-infinitepay-gateway' ) . '</h2>';
        echo '<table class="widefat striped"><thead><tr>';
        foreach ( array( __( 'Pedido', 'jesb-infinitepay-gateway' ), __( 'Data', 'jesb-infinitepay-gateway' ), __( 'Cliente', 'jesb-infinitepay-gateway' ), __( 'Valor', 'jesb-infinitepay-gateway' ), __( 'Status WooCommerce', 'jesb-infinitepay-gateway' ), __( 'Pagamento', 'jesb-infinitepay-gateway' ) ) as $heading ) {
            echo '<th scope="col">' . esc_html( $heading ) . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ( array_slice( $records, 0, 25 ) as $order ) {
            $date = $order->get_date_created();
            $method = $order->get_meta( '_jesb_ipg_payment_method', true );
            $method = $method ? sanitize_text_field( (string) $method ) : __( 'Não identificado', 'jesb-infinitepay-gateway' );
            echo '<tr><td><a href="' . esc_url( $order->get_edit_order_url() ) . '">#' . esc_html( $order->get_order_number() ) . '</a></td>';
            echo '<td>' . esc_html( $date ? $date->date_i18n( get_option( 'date_format' ) ) : '—' ) . '</td>';
            echo '<td>' . esc_html( $order->get_formatted_billing_full_name() ) . '</td>';
            echo '<td>' . wp_kses_post( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ) . '</td>';
            echo '<td>' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '</td>';
            echo '<td>' . esc_html( $method ) . '</td></tr>';
        }
        if ( ! $records ) {
            echo '<tr><td colspan="6">' . esc_html__( 'Nenhum pedido recente da integração foi encontrado.', 'jesb-infinitepay-gateway' ) . '</td></tr>';
        }
        echo '</tbody></table><p><a class="button button-primary" href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Configurações do gateway', 'jesb-infinitepay-gateway' ) . '</a></p>';
        echo '<p>' . esc_html__( 'Valores de taxas, saldos e liquidações não são apresentados sem confirmação verificável. Acesso restrito a usuários com capacidade manage_woocommerce.', 'jesb-infinitepay-gateway' ) . '</p></div>';
    }
}
