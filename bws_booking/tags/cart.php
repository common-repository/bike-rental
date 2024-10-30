<?php /**
 * Contains the list of functions are used to handle cart
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( ! function_exists( 'bws_bkng_add_to_cart_link' ) ) {
    /**
     * Displays the "Add to cart" link
     * @since    0.1
     * @param    mixed         $post    Curren post data
     * @return   bool|void
     */
	function bws_bkng_add_to_cart_link( $post = null ) {

		$post = get_post( $post );

		if ( empty( $post->ID ) )
			return false;

		$query = bws_bkng_get_query();
		$link  = add_query_arg(
			array(
				'bkng_action'  => 'add',
				'bkng_product' => $post->ID,
				'bkng_from'    => empty( $query['search']['from'] ) ? false : $query['search']['from'],
				'bkng_till'    => empty( $query['search']['till'] ) ? false : $query['search']['till'],
				'bkng_nonce'   => wp_create_nonce( "bkng_add_{$post->ID}" )
			),
			get_the_permalink( $post )
		);

		$label = __( 'Add to cart', 'bws_bkng' );

		echo "<a class=\"bws_bkng_add_to_cart_link\" href=\"" . esc_url( $link ) . "\">" . esc_html( $label ) . "</a>";
	}
}


if ( ! function_exists( 'bws_bkng_get_cart' ) ) {
    /**
     * @since    0.1
     * @return   array
     */
	function bws_bkng_get_cart() {
		$cart = BWS_BKNG_Cart::get_instance();
		return $cart->get();
	}
}

/**
 *
 * @since    0.1
 * @param    void
 * @return   void
 */
if ( ! function_exists( 'bws_bkng_display_cart' ) ) {
	function bws_bkng_display_cart() {
		$cart = BWS_BKNG_Cart::get_instance();
		$cart->table();
	}
}

if ( ! function_exists( 'bws_bkng_add_extras_to_cart_link' ) ) {
    /**
     * Initial template
     * @since    0.1
     * @param    $post
     * @return   void|bool
     */
	function bws_bkng_add_extras_to_cart_link( $post = null ) {

		$post = get_post( $post );

		if ( empty( $post->ID ) )
			return false;

		$link = add_query_arg(
			array(
				'bkng_action' => 'add_extras',
			),
			get_the_permalink( $post )
		);

		$label = __( 'Add extras to cart', 'bws_bkng' );

		echo "<a class=\"bws_bkng_add_extras_to_cart_link\" href=\"" . esc_url( $link ) . "\">" . esc_html( $label ) . "</a>";
	}
}