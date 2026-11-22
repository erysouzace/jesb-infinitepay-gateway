<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/** Persist payment attempts on the WooCommerce order via CRUD (HPOS-safe). */
final class JESB_IPG_Attempts {
    public static function all( WC_Order $order ) {
        $records = $order->get_meta( '_jesb_ipg_attempts', true );
        return is_array( $records ) ? $records : array();
    }
    public static function find( WC_Order $order, $nsu ) {
        foreach ( self::all( $order ) as $record ) {
            if ( isset( $record['nsu'] ) && is_string( $record['nsu'] ) && hash_equals( $record['nsu'], (string) $nsu ) ) {
                return $record;
            }
        }
        // Preserve compatibility with payments created before version 1.1.1.
        if ( hash_equals( (string) $order->get_meta( '_jesb_ipg_order_nsu', true ), (string) $nsu ) && '' !== (string) $nsu ) {
            return array( 'nsu' => $nsu, 'amount' => (int) $order->get_meta( '_jesb_ipg_amount_cents', true ), 'legacy' => true );
        }
        return null;
    }
    public static function current( WC_Order $order ) {
        return self::find( $order, (string) $order->get_meta( '_jesb_ipg_order_nsu', true ) );
    }
    public static function put( WC_Order $order, array $record ) {
        $all = self::all( $order );
        $replaced = false;
        foreach ( $all as $index => $existing ) {
            if ( isset( $existing['nsu'] ) && hash_equals( (string) $existing['nsu'], (string) $record['nsu'] ) ) {
                $all[$index] = array_merge( $existing, $record );
                $replaced = true;
                break;
            }
        }
        if ( ! $replaced ) { $all[] = $record; }
        // Never discard old attempt references: older checkout links can still produce callbacks.
        $order->update_meta_data( '_jesb_ipg_attempts', $all );
        $order->save();
    }
    public static function lock( $id, $suffix ) {
        $key = 'jesb_ipg_' . $suffix . '_' . absint( $id );
        $now = time();
        if ( add_option( $key, $now, '', 'no' ) ) { return $key; }
        // Stale cleanup is intentionally conservative: wait longer than network timeout.
        $since = (int) get_option( $key, 0 );
        if ( $since > 0 && $since < $now - 120 ) {
            delete_option( $key );
            if ( add_option( $key, $now, '', 'no' ) ) { return $key; }
        }
        return '';
    }
    public static function unlock( $key ) {
        if ( $key ) { delete_option( $key ); }
    }
}
