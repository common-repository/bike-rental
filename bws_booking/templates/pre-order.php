<?php
/**
 * Displays the pre-order data
 * @uses     To visualize the cost of an order before placing it
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

$show_price_and_totals = bws_bkng_show_product_price();

do_action( 'bws_bkng_before_pre_order' ); ?>

<div class="bws_bkng_order_data">

	<h3><?php the_title(); ?></h3>

	<?php bws_bkng_get_template_part( 'product-attributes' ); ?>

	<h4 class="bws_bkng_order_product_price<?php if ( ! $show_price_and_totals ) echo ' bkng_hidden'; ?>"><?php _e( "Price", BWS_BKNG_TEXT_DOMAIN ); ?>:&nbsp;<?php bws_bkng_product_price(); ?></h4>

	<?php if ( 'available' == bws_bkng_get_product_status() ) { ?>

		<h4><?php _e( "Pick-up drop-off dates", BWS_BKNG_TEXT_DOMAIN ); ?>:</h4>
		<div class="bws_bkng_product_rent_interval"></div>

		<h4><?php _e( "Quantity", BWS_BKNG_TEXT_DOMAIN ); ?>:&nbsp;<span class="bws_bkng_order_product_quantity">1</span></h4>

		<h4<?php if ( ! $show_price_and_totals ) echo ' class="bkng_hidden"'; ?>><?php _e( "Order Subtotal", BWS_BKNG_TEXT_DOMAIN ); ?>:&nbsp;<?php echo bws_bkng_price_format( bws_bkng_pre_order_subtotal(), 'bws_bkng_product_subtotal' ); ?></h4>

		<h4><?php _e( "Extras", BWS_BKNG_TEXT_DOMAIN ); ?>:</h4>
		<div class="bws_bkng_selected_extras">
			<?php _e( 'No chosen extras yet', BWS_BKNG_TEXT_DOMAIN ); ?>
		</div>

		<h4 class="bws_bkng_extras_subtotal_wrap<?php if ( ! $show_price_and_totals ) echo ' bkng_hidden'; ?>"><?php _e( "Extras Subtotal", BWS_BKNG_TEXT_DOMAIN ); ?>:&nbsp;<?php echo bws_bkng_price_format( '0', 'bws_bkng_extras_subtotal' ); ?></h4>

		<h4 class="bws_bkng_order_total_wrap<?php if ( ! $show_price_and_totals ) echo ' bkng_hidden'; ?>"><?php _e( "Subtotal", BWS_BKNG_TEXT_DOMAIN ); ?>:&nbsp;<?php echo bws_bkng_price_format( bws_bkng_pre_order_subtotal(), 'bws_bkng_order_total' ); ?>
		</h4>

	<?php } else {

		bws_bkng_product_status_notice();

	} ?>

</div><!-- .bws_bkng_order_data -->

<?php do_action( 'bws_bkng_after_pre_order' );

