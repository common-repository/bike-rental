<?php /**
 * Contains the list of functions are used to handle orders
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( ! function_exists( 'bws_bkng_pre_order_subtotal' ) ) {
	function bws_bkng_pre_order_subtotal() {
		$pre_order = BWS_BKNG_Pre_Order::get_instance();
		return $pre_order->get_subtotal();
	}
}

/**
 * The function is necessary when after placing an order on the site,
 * the currency settings have been changed *
 */
if ( ! function_exists( 'bws_bkng_get_order_price_format' ) ) {
	function bws_bkng_get_order_price_format( $order ='' ) {
		global $bws_bkng;

		if ( empty( $order ) || ! is_array( $order ) )
			return array();

		$format = array();

		if ( ! empty( $order['currency_code'] ) ) {
			$currencies = $bws_bkng->data_loader->load( 'currencies' );
			$format['currency'] = $currencies[ $order['currency_code'] ][1];
		}

		if ( ! empty( $order['currency_position'] ) )
			$format['currency_position'] = $order['currency_position'];

		return $format;
	}
}

/**
 *
 * @since    0.1
 * @param    void
 * @return   void
 */
if ( ! function_exists( 'bkng_get_order_id' ) ) {
	function bkng_get_order_id() {
		return empty( $_REQUEST['bkng_order'] ) ? false : absint( $_REQUEST['bkng_order'] );
	}
}

/**
 *
 * @since    0.1
 * @param    void
 * @return   array
 */
if ( ! function_exists( 'bws_bkng_get_order' ) ) {
	function bws_bkng_get_order( $order_id = '' ) {
		$order = BWS_BKNG_Order::get_instance();
		return $order->get( $order_id );
	}
}

if ( ! function_exists( 'bws_bkng_get_billing_data' ) ) {
	function bws_bkng_get_billing_data() {
		$order = BWS_BKNG_Order::get_instance();
		return $order->get_billing_data();
	}
}

if ( ! function_exists( 'bws_bkng_get_terms_and_conditions' ) ) {
	function bws_bkng_get_terms_and_conditions( $post_type = '' ) {
		global $wpdb;

		if ( '' !== $post_type ) {
			$post_type .= '_';
		}

		$table = BWS_BKNG_DB_PREFIX . $post_type . "notifications";

		return $wpdb->get_var(
            "SELECT `body` FROM `{$table}` WHERE `type` = 'terms_and_conditions' LIMIT 1;"
        );
	}
}

if ( ! function_exists( 'bws_bkng_get_ordered_product' ) ) {
	function bws_bkng_get_ordered_product( $order ) {
		if( empty( $order ) ) {
			return false;
		}
		/* first item in the list of products is always the main ordered product */
		$product = reset( $order['products'] );
		$product['id'] = key( $order['products'] );
		return $product;
	}
}

if ( ! function_exists( 'bws_bkng_show_order_totals' ) ) {
	function bws_bkng_show_order_totals( $order ) {

		foreach( $order['products'] as $id => $product ) {
			if ( ! bws_bkng_show_product_price( $id ) )
				return false;
		}

		return true;
	}
}

/**
 *
 * @since    0.1
 * @param    void
 * @return   void
 */
if ( ! function_exists( 'bws_bkng_display_order' ) ) {
	function bws_bkng_display_order() {
		$order = BWS_BKNG_Order::get_instance();
		$order->display();
	}
}
