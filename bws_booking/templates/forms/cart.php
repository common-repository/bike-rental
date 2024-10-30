<?php
/**
 * Displays the cart form
 * @since    Booking 0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

if ( bws_bkng_get_cart() ) {

	bws_bkng_errors( 'cart' );

	do_action( 'bws_bkng_before_cart_form' ); ?>

	<form class="bws_bkng_cart_form" method="post" action="<?php echo esc_url( bws_bkng_get_page_permalink( 'cart' ) ); ?>">

		<?php bws_bkng_display_cart(); ?>

		<p class="submit">

			<noscript>
				<input type="submit" name="bkng_update_cart" class="button button-primary" value="<?php _e( 'Update Cart', BWS_BKNG_TEXT_DOMAIN ); ?>" />
			</noscript>

			<input type="submit" name="bkng_checkout_product" class="button button-primary bws_bkng_cart_button" value="<?php _e( 'Place Order', BWS_BKNG_TEXT_DOMAIN ); ?>" />

			<a href="<?php echo esc_url( bws_bkng_get_page_permalink( 'products' ) ); ?>"><?php _e( 'Back to products', BWS_BKNG_TEXT_DOMAIN ); ?></a>

			<input type="hidden" name="bkng_nonce" value="<?php echo wp_create_nonce( "bkng_checkout_product" ); ?>" />

		</p>

	</form>

	<?php do_action( 'bws_bkng_after_cart_form' );

} else {
	do_action( 'bws_bkng_empty_cart' );

	_e( 'There is nothing in the cart', BWS_BKNG_TEXT_DOMAIN );

}