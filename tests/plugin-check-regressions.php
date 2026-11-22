<?php
$root = dirname( __DIR__ );
$readme = file_get_contents( $root . '/readme.txt' );
$data = file_get_contents( $root . '/includes/class-jesb-ipg-checkout-data.php' );
$admin = file_get_contents( $root . '/includes/class-jesb-ipg-admin.php' );
$gateway = file_get_contents( $root . '/includes/class-jesb-ipg-gateway.php' );
$main = file_get_contents( $root . '/jesb-infinitepay-gateway.php' );
$checks = array(
    'readme tested metadata' => (bool) preg_match( '/^Tested up to: [0-9]+\.[0-9]+$/m', $readme ),
    'ordered translators placeholders' => str_contains( $data, 'Pedido #%1$s — %2$s' ),
    'translator comment in checkout' => str_contains( $data, 'translators: 1:' ),
    'admin filter has nonce validation' => str_contains( $admin, 'wp_verify_nonce(' ),
    'admin filter emits nonce' => str_contains( $admin, 'wp_create_nonce(' ),
    'provider return verified by external API' => str_contains( $gateway, 'JESB_IPG_Confirmation::verify(' ),
    'provider return key validation' => str_contains( $gateway, 'hash_equals( $order->get_order_key()' ),
    'no discouraged translation loader' => ! str_contains( $main, 'load_plugin_textdomain(' ),
);
foreach ( $checks as $label => $ok ) {
    if ( ! $ok ) { fwrite( STDERR, "FAIL: {$label}\n" ); exit( 1 ); }
    echo "OK: {$label}\n";
}
