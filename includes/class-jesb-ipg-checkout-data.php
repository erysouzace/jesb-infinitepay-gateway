<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Maps existing WooCommerce order data to the official hosted checkout fields. */
final class JESB_IPG_Checkout_Data {
    public static function items( WC_Order $order ) {
        $target = JESB_IPG_API::cents( $order->get_total() );
        $entries = array();
        foreach ( $order->get_items( 'line_item' ) as $item ) {
            if ( ! $item instanceof WC_Order_Item_Product ) { continue; }
            $amount = JESB_IPG_API::cents( (float) $item->get_total() + (float) $item->get_total_tax() );
            $qty = max( 1, (int) $item->get_quantity() );
            if ( $amount <= 0 ) { continue; }
            $title = self::title( $item->get_name() );
            // Split only when the line amount does not divide evenly by quantity.
            $unit = intdiv( $amount, $qty );
            $remainder = $amount - $unit * $qty;
            if ( $unit > 0 ) {
                $entries[] = array( 'description' => $title, 'quantity' => $qty, 'price' => $unit );
            }
            if ( $remainder > 0 ) {
                $entries[] = array( 'description' => sprintf( __( 'Ajuste de arredondamento: %s', 'jesb-infinitepay-gateway' ), $title ), 'quantity' => 1, 'price' => $remainder );
            }
        }
        foreach ( $order->get_items( 'shipping' ) as $shipping ) {
            $value = JESB_IPG_API::cents( (float) $shipping->get_total() + (float) $shipping->get_total_tax() );
            if ( $value > 0 ) {
                $entries[] = array( 'description' => self::title( __( 'Frete', 'jesb-infinitepay-gateway' ) . ': ' . $shipping->get_name() ), 'quantity' => 1, 'price' => $value );
            }
        }
        foreach ( $order->get_items( 'fee' ) as $fee ) {
            $value = JESB_IPG_API::cents( (float) $fee->get_total() + (float) $fee->get_total_tax() );
            if ( $value > 0 ) {
                $entries[] = array( 'description' => self::title( __( 'Taxa adicional', 'jesb-infinitepay-gateway' ) . ': ' . $fee->get_name() ), 'quantity' => 1, 'price' => $value );
            }
        }
        $sum = 0;
        foreach ( $entries as $entry ) { $sum += $entry['quantity'] * $entry['price']; }
        $difference = $target - $sum;
        // WooCommerce may have order-level discounts and negative fees; distribute them
        // across positive payable amounts without ever sending a negative API item.
        if ( $difference < 0 ) {
            $remaining = -$difference;
            for ( $i = count( $entries ) - 1; $i >= 0 && $remaining > 0; --$i ) {
                $entry = $entries[$i];
                $total = $entry['price'] * $entry['quantity'];
                $take = min( $remaining, $total );
                if ( 0 === $take ) { continue; }
                $new_total = $total - $take;
                array_splice( $entries, $i, 1 );
                if ( $new_total > 0 ) {
                    array_splice( $entries, $i, 0, array( array(
                        'description' => $entry['description'], 'quantity' => 1, 'price' => $new_total,
                    ) ) );
                }
                $remaining -= $take;
            }
            if ( $remaining > 0 ) { return self::fallback( $order, $target ); }
        } elseif ( $difference > 0 ) {
            $entries[] = array( 'description' => __( 'Ajuste do total do pedido', 'jesb-infinitepay-gateway' ), 'quantity' => 1, 'price' => $difference );
        }
        if ( ! $entries ) { return self::fallback( $order, $target ); }
        $verified_sum = 0;
        foreach ( $entries as $entry ) {
            if ( $entry['price'] <= 0 || $entry['quantity'] <= 0 ) { return self::fallback( $order, $target ); }
            $verified_sum += $entry['price'] * $entry['quantity'];
        }
        if ( $verified_sum !== $target || count( $entries ) > 100 ) { return self::fallback( $order, $target ); }
        return array_values( $entries );
    }

    private static function fallback( WC_Order $order, $total ) {
        return array( array(
            'description' => sprintf( __( 'Pedido #%s — %s', 'jesb-infinitepay-gateway' ), $order->get_order_number(), get_bloginfo( 'name' ) ),
            'quantity' => 1, 'price' => $total,
        ) );
    }

    private static function title( $value ) {
        $value = trim( wp_strip_all_tags( (string) $value ) );
        if ( function_exists( 'mb_substr' ) ) { return mb_substr( $value, 0, 110 ); }
        return substr( $value, 0, 110 );
    }

    public static function customer( WC_Order $order ) {
        $customer = array();
        $name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
        if ( '' !== $name ) { $customer['name'] = self::title( $name ); }
        $email = $order->get_billing_email();
        if ( is_email( $email ) ) { $customer['email'] = sanitize_email( $email ); }
        $phone = preg_replace( '/\D+/', '', $order->get_billing_phone() );
        if ( preg_match( '/^[1-9][0-9]{9,10}$/D', $phone ) ) { $phone = '55' . $phone; }
        if ( preg_match( '/^[1-9][0-9]{11,14}$/D', $phone ) ) { $customer['phone_number'] = '+' . $phone; }
        return $customer;
    }

    public static function has_physical_items( WC_Order $order ) {
        foreach ( $order->get_items( 'line_item' ) as $item ) {
            if ( ! $item instanceof WC_Order_Item_Product ) { return true; }
            $product = $item->get_product();
            if ( ! $product || ! $product->is_virtual() ) { return true; }
        }
        return false;
    }

    public static function address( WC_Order $order ) {
        if ( ! self::has_physical_items( $order ) ) { return array(); }
        $shipping = '' !== $order->get_shipping_address_1();
        $prefix = $shipping ? 'shipping' : 'billing';
        $get = static function ( $field ) use ( $order, $shipping ) {
            $method = 'get_' . ( $shipping ? 'shipping_' : 'billing_' ) . $field;
            return (string) $order->$method();
        };
        if ( 'BR' !== $get( 'country' ) ) { return array(); }
        $street = trim( $get( 'address_1' ) );
        if ( '' === $street ) { return array(); }
        $number = self::meta( $order, $prefix . '_number' );
        $neighborhood = self::meta( $order, $prefix . '_neighborhood' );
        // Supports regular Brazilian address fields saved as "Rua X, 123".
        if ( '' === $number && preg_match( '/^(.+?),\s*(\d+[a-zA-Z]?)\s*$/u', $street, $matches ) ) {
            $street = trim( $matches[1] );
            $number = $matches[2];
        }
        $cep = preg_replace( '/\D+/', '', $get( 'postcode' ) );
        $state = strtoupper( trim( $get( 'state' ) ) );
        $address = array(
            'street' => self::title( $street ),
            'city' => self::title( $get( 'city' ) ),
            'state' => $state,
        );
        if ( '' !== $number ) { $address['number'] = self::title( $number ); }
        if ( '' !== $neighborhood ) { $address['neighborhood'] = self::title( $neighborhood ); }
        if ( strlen( $cep ) === 8 ) { $address['cep'] = $cep; }
        $complement = trim( $get( 'address_2' ) );
        if ( '' !== $complement ) { $address['complement'] = self::title( $complement ); }
        if ( '' === $address['city'] || ! preg_match( '/^[A-Z]{2}$/D', $state ) ) { return array(); }
        return $address;
    }

    private static function meta( WC_Order $order, $key ) {
        foreach ( array( '_' . $key, $key ) as $meta_key ) {
            $value = trim( (string) $order->get_meta( $meta_key, true ) );
            if ( '' !== $value ) { return $value; }
        }
        return '';
    }
}
