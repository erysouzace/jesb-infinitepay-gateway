<?php
$plugin = file_get_contents( __DIR__ . '/../jesb-infinitepay-gateway.php' );
$readme = file_get_contents( __DIR__ . '/../readme.txt' );
preg_match( '/^ \* Version: ([0-9.]+)/m', $plugin, $version );
preg_match( '/^Stable tag: ([0-9.]+)/m', $readme, $stable );
if ( ! isset( $version[1], $stable[1] ) || $version[1] !== $stable[1] ) {
    fwrite( STDERR, "Plugin and readme versions mismatch\n" );
    exit( 1 );
}
echo "Version metadata OK: {$version[1]}\n";
