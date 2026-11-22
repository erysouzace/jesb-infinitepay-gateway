<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Bounded, read-only WooCommerce order report (not a settlement statement). */
final class JESB_IPG_Admin {
    public static function init() { add_action( 'admin_menu', array( __CLASS__, 'menu' ), 30 ); }
    public static function menu() {
        add_submenu_page( 'woocommerce', __( 'JESB InfinitePay — Financeiro', 'jesb-infinitepay-gateway' ), __( 'JESB InfinitePay', 'jesb-infinitepay-gateway' ), 'manage_woocommerce', 'jesb-infinitepay-financeiro', array( __CLASS__, 'render' ) );
    }
    public static function render() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( esc_html__( 'Acesso negado.', 'jesb-infinitepay-gateway' ) ); }
        if ( ! function_exists( 'wc_get_orders' ) ) { return; }
        // A period filter is read-only, but the admin form includes a nonce to
        // prevent unauthenticated or unintended query processing.
        $days = 30;
        if ( isset( $_GET['jesb_period'], $_GET['jesb_period_nonce'] ) && is_scalar( $_GET['jesb_period'] ) && is_string( $_GET['jesb_period_nonce'] ) ) {
            $period_nonce = sanitize_text_field( wp_unslash( $_GET['jesb_period_nonce'] ) );
            if ( wp_verify_nonce( $period_nonce, 'jesb_ipg_period_filter' ) ) {
                $days = absint( wp_unslash( $_GET['jesb_period'] ) );
            }
        }
        if ( ! in_array( $days, array( 7, 30, 90 ), true ) ) { $days = 30; }
        $from = time() - DAY_IN_SECONDS * $days;
        $paid = 0; $pending = 0; $gross = 0; $needs_review = 0; $records = array();
        $sampled = 0; $max_pages = 20; $truncated = false;
        for ( $page = 1; $page <= $max_pages; ++$page ) {
            $batch = wc_get_orders( array(
                'limit' => 100, 'page' => $page, 'paginate' => true,
                'orderby' => 'date', 'order' => 'DESC', 'return' => 'objects',
                'date_created' => '>' . $from,
            ) );
            if ( ! is_object( $batch ) || ! isset( $batch->orders ) ) { break; }
            foreach ( $batch->orders as $order ) {
                if ( ! $order instanceof WC_Order ) { continue; }
                ++$sampled;
                if ( 'jesb_infinitepay' !== $order->get_payment_method() ) { continue; }
                if ( count( $records ) < 25 ) { $records[] = $order; }
                if ( $order->is_paid() ) {
                    ++$paid; $gross += (float) $order->get_total();
                } elseif ( $order->has_status( array( 'pending', 'on-hold', 'failed' ) ) ) { ++$pending; }
                if ( 'yes' === $order->get_meta( '_jesb_ipg_attention_required', true ) ) { ++$needs_review; }
            }
            if ( $page >= (int) $batch->max_num_pages ) { break; }
            if ( $page === $max_pages ) { $truncated = true; }
        }
        $settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=jesb_infinitepay' );
        echo '<div class="wrap"><h1>' . esc_html__( 'JESB InfinitePay — Financeiro', 'jesb-infinitepay-gateway' ) . '</h1>';
        echo '<p>' . esc_html__( 'Relatório de pedidos WooCommerce, não representa liquidações, taxas reais ou saldo bancário.', 'jesb-infinitepay-gateway' ) . '</p>';
        echo '<form method="get"><input type="hidden" name="page" value="jesb-infinitepay-financeiro"><input type="hidden" name="jesb_period_nonce" value="' . esc_attr( wp_create_nonce( 'jesb_ipg_period_filter' ) ) . '"><label for="jesb_period">' . esc_html__( 'Período', 'jesb-infinitepay-gateway' ) . '</label> <select id="jesb_period" name="jesb_period">';
        foreach ( array( 7, 30, 90 ) as $period ) {
            // translators: %d is the number of days in the financial report period.
            echo '<option value="' . esc_attr( (string) $period ) . '" ' . selected( $days, $period, false ) . '>' . esc_html( sprintf( __( 'Últimos %d dias', 'jesb-infinitepay-gateway' ), $period ) ) . '</option>';
        }
        echo '</select> <button class="button">' . esc_html__( 'Filtrar', 'jesb-infinitepay-gateway' ) . '</button></form>';
        if ( $truncated ) { echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Relatório parcial: limite de 2.000 pedidos da loja neste período. Reduza o intervalo para consultar uma amostra completa.', 'jesb-infinitepay-gateway' ) . '</p></div>'; }
        echo '<div style="display:flex;flex-wrap:wrap;gap:12px;margin:18px 0">';
        $cards = array(
            __( 'Pagamentos aprovados', 'jesb-infinitepay-gateway' ) => (string) $paid,
            __( 'Pendentes', 'jesb-infinitepay-gateway' ) => (string) $pending,
            __( 'Bruto aprovado', 'jesb-infinitepay-gateway' ) => wp_strip_all_tags( wc_price( $gross ) ),
            __( 'Verificação necessária', 'jesb-infinitepay-gateway' ) => (string) $needs_review,
        );
        foreach ( $cards as $heading => $value ) {
            echo '<div style="min-width:190px;flex:1;max-width:320px;background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:18px">';
            echo '<div style="color:#50575e">' . esc_html( $heading ) . '</div><div style="font-size:23px;font-weight:700;margin-top:8px">' . esc_html( $value ) . '</div></div>';
        }
        echo '</div><h2>' . esc_html__( 'Transações recentes', 'jesb-infinitepay-gateway' ) . '</h2><table class="widefat striped"><thead><tr>';
        foreach ( array( __( 'Pedido', 'jesb-infinitepay-gateway' ), __( 'Data', 'jesb-infinitepay-gateway' ), __( 'Cliente', 'jesb-infinitepay-gateway' ), __( 'Valor', 'jesb-infinitepay-gateway' ), __( 'Status', 'jesb-infinitepay-gateway' ), __( 'Pagamento', 'jesb-infinitepay-gateway' ), __( 'Atenção', 'jesb-infinitepay-gateway' ) ) as $heading ) { echo '<th scope="col">' . esc_html( $heading ) . '</th>'; }
        echo '</tr></thead><tbody>';
        foreach ( $records as $order ) {
            $date = $order->get_date_created();
            $method = (string) $order->get_meta( '_jesb_ipg_payment_method', true );
            echo '<tr><td><a href="' . esc_url( $order->get_edit_order_url() ) . '">#' . esc_html( $order->get_order_number() ) . '</a></td>';
            echo '<td>' . esc_html( $date ? $date->date_i18n( get_option( 'date_format' ) ) : '—' ) . '</td>';
            echo '<td>' . esc_html( $order->get_formatted_billing_full_name() ) . '</td>';
            echo '<td>' . wp_kses_post( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ) . '</td>';
            echo '<td>' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '</td>';
            echo '<td>' . esc_html( $method ? $method : __( 'Não identificado', 'jesb-infinitepay-gateway' ) ) . '</td>';
            echo '<td>' . ( 'yes' === $order->get_meta( '_jesb_ipg_attention_required', true ) ? esc_html__( 'Conciliar manualmente', 'jesb-infinitepay-gateway' ) : '—' ) . '</td></tr>';
        }
        if ( ! $records ) { echo '<tr><td colspan="7">' . esc_html__( 'Nenhum pedido da integração neste período.', 'jesb-infinitepay-gateway' ) . '</td></tr>'; }
        echo '</tbody></table><p><a class="button button-primary" href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Configurações do gateway', 'jesb-infinitepay-gateway' ) . '</a></p>';
        echo '<p>' . esc_html__( 'Acesso restrito a manage_woocommerce. Pagamentos pendentes não são considerados faturamento recebido. Taxas reais e liquidação não são fornecidas pela API de checkout.', 'jesb-infinitepay-gateway' ) . '</p></div>';
    }
}
