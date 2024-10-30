<?php
/**
 * Display the "Checkout" button on the single  product page
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

if ( 'available' == bws_bkng_get_product_status() ) {
    global $post; ?>

	<form class="bws_bkng_to_checkout_button" method="post" action="<?php echo esc_url( bws_bkng_get_page_permalink( 'checkout' ) ); ?>">
		<p class="submit">
			<input type="submit" name="bkng_checkout_product" class="button button-primary" value="<?php _e( 'Checkout', BWS_BKNG_TEXT_DOMAIN ); ?>" />
			<?php bws_bkng_back_to_search_link();
			if ( ! bws_bkng_product_is_in_cart() )
				bws_bkng_add_to_cart_link(); ?>
			<input type="hidden" name="bkng_product" value="<?php echo esc_attr( $post->ID ); ?>" />
			<input type="hidden" name="bkng_nonce" value="<?php echo wp_create_nonce( "bkng_checkout_product" ); ?>" />
		</p>
	</form><!-- #bws_bkng_to_checkout_form -->

<?php } else {

	bws_bkng_product_status_notice();

} ?>