<?php
/**
 * Displays the product price
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

do_action( 'bws_bkng_before_price' ); ?>

<div class="bws_bkng_product_price_row">
	<?php if ( bws_bkng_show_product_price() )
			bws_bkng_product_price();
		else
			_e( 'Price on request', BWS_BKNG_TEXT_DOMAIN ); ?>
</div><!-- .bws_bkng_product_price_row -->

<?php do_action( 'bws_bkng_after_price' ); ?>

