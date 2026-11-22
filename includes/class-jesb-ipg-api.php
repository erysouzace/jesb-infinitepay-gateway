<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class JESB_IPG_API {
    private const BASE = 'https://api.checkout.infinitepay.io/';

    public static function settings() {
        return (array) get_option( 'woocommerce_jesb_infinitepay_settings', array() );
    }
    public static function handle() {
        $settings = self::settings();
        return isset( $settings['handle'] ) ? ltrim( trim( (string) $settings['handle'] ), '$' ) : '';
    }
    public static function configuration_issues() {
        $issues = array();
        if ( '' === self::handle() ) {
            $issues[] = __( 'Preencha e salve a InfiniteTag nas configurações do método.', 'jesb-infinitepay-gateway' );
        }
        if ( 'BRL' !== get_woocommerce_currency() ) {
            $issues[] = __( 'A moeda da loja deve ser Real brasileiro (BRL).', 'jesb-infinitepay-gateway' );
        }
        if ( ! self::transport_ready() ) {
            $issues[] = __( 'O endereço do checkout e o endpoint de webhook devem utilizar HTTPS.', 'jesb-infinitepay-gateway' );
        }
        return $issues;
    }
    public static function transport_ready() {
        return 'https' === wp_parse_url( wc_get_checkout_url(), PHP_URL_SCHEME )
            && 'https' === wp_parse_url( rest_url( 'jesb-infinitepay/v1/webhook' ), PHP_URL_SCHEME );
    }
    public static function log( $message, $context = array(), $level = 'info' ) {
        $settings = self::settings();
        if ( 'yes' !== ( $settings['debug'] ?? 'no' ) || ! function_exists( 'wc_get_logger' ) ) { return; }
        // Never log request payloads, tokens, API credentials, names, emails or URLs with order keys.
        $safe = array();
        foreach ( array( 'order_id', 'http_code', 'reason', 'event' ) as $key ) {
            if ( isset( $context[ $key ] ) && is_scalar( $context[ $key ] ) ) {
                $safe[ $key ] = substr( sanitize_text_field( (string) $context[ $key ] ), 0, 80 );
            }
        }
        wc_get_logger()->log( $level, $message . ' ' . wp_json_encode( $safe ), array( 'source' => 'jesb-infinitepay-gateway' ) );
    }
    public static function request( $path, $payload ) {
        $settings = self::settings();
        $headers  = array( 'Content-Type' => 'application/json', 'Accept' => 'application/json' );
        // Prefer a constant in wp-config.php for secrets (not stored in database).
        $api_key = defined( 'JESB_INFINITEPAY_API_KEY' ) ? (string) JESB_INFINITEPAY_API_KEY : '';
        if ( '' !== $api_key ) { $headers['X-Api-Key'] = $api_key; }
        $response = wp_safe_remote_post( self::BASE . $path, array(
            'timeout' => 18,
            'redirection' => 0,
            'headers' => $headers,
            'body' => wp_json_encode( $payload ),
        ) );
        if ( is_wp_error( $response ) ) {
            self::log( 'Falha de comunicação com o processador.', array( 'reason' => 'transport' ), 'error' );
            return new WP_Error( 'jesb_ipg_network', __( 'Não foi possível contactar a InfinitePay. Tente novamente.', 'jesb-infinitepay-gateway' ) );
        }
        $code = wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) {
            self::log( 'API retornou resposta não bem-sucedida.', array( 'http_code' => $code ), 'error' );
            return new WP_Error( 'jesb_ipg_http', __( 'A InfinitePay não conseguiu processar a solicitação. Tente novamente.', 'jesb-infinitepay-gateway' ) );
        }
        $body = wp_remote_retrieve_body( $response );
        if ( strlen( $body ) > 65536 ) { return new WP_Error( 'jesb_ipg_oversize', __( 'Resposta de pagamento inválida.', 'jesb-infinitepay-gateway' ) ); }
        $data = json_decode( $body, true );
        if ( ! is_array( $data ) ) { return new WP_Error( 'jesb_ipg_json', __( 'Resposta de pagamento inválida.', 'jesb-infinitepay-gateway' ) ); }
        return $data;
    }
    public static function cents( $amount ) {
        return (int) round( (float) wc_format_decimal( $amount, wc_get_price_decimals() ) * 100, 0 );
    }
    public static function is_valid_payment_url( $url ) {
        if ( ! is_string( $url ) || strlen( $url ) > 2048 ) { return false; }
        $parts = wp_parse_url( $url );
        if ( ! is_array( $parts ) || ( $parts['scheme'] ?? '' ) !== 'https' ) { return false; }
        $host = strtolower( $parts['host'] ?? '' );
        return in_array( $host, array( 'checkout.infinitepay.io', 'checkout.infinitepay.com.br', 'buy.infinitepay.io' ), true )
            && empty( $parts['user'] ) && empty( $parts['pass'] )
            && ( ! isset( $parts['port'] ) || 443 === (int) $parts['port'] );
    }
}
