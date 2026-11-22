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
    /**
     * Use a connection-bound MySQL/MariaDB advisory lock instead of a timed
     * option lock. The database releases it when a worker disconnects, and
     * no stale-lock scavenger can accidentally delete a newer worker's lock.
     * Fail closed if advisory locking is unavailable.
     */
    private static $held_locks = array();

    public static function lock( $id, $suffix ) {
        global $wpdb;
        if ( ! $wpdb instanceof wpdb || ! in_array( $suffix, array( 'create', 'confirm' ), true ) ) {
            return '';
        }
        $name = 'jesb_ipg_' . $suffix . '_' . absint( $id );
        if ( isset( self::$held_locks[ $name ] ) ) {
            return '';
        }
        $result = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $name ) );
        if ( '1' !== (string) $result ) {
            return '';
        }
        self::$held_locks[ $name ] = true;
        return $name;
    }

    public static function unlock( $name ) {
        global $wpdb;
        if ( ! $name || ! isset( self::$held_locks[ $name ] ) ) {
            return;
        }
        if ( $wpdb instanceof wpdb ) {
            $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $name ) );
        }
        unset( self::$held_locks[ $name ] );
    }
}
