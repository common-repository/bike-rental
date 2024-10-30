<?php
/**
 * Displays the order and billing details after placing the order
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

$order_id = bkng_get_order_id();
$order    = bws_bkng_get_order( $order_id );

if ( $order ) {

	$price_format = bws_bkng_get_order_price_format( $order );
	$product      = bws_bkng_get_ordered_product( $order );
	$show_totals  = bws_bkng_show_order_totals( $order );

	do_action( 'bws_bkng_before_order' ); ?>

	<div class="bws_bkng_order_data">

		<?php if ( $order_id ) { ?>
			<h3>
				<?php echo __( 'Your order', BWS_BKNG_TEXT_DOMAIN ); ?>:
				<span class="bws_bkng_order_id"><?php echo esc_html( $order_id ) ?></span>
				<span class="bws_bkng_order_date_create"><?php echo __( 'from', BWS_BKNG_TEXT_DOMAIN ) . '&nbsp;' . date_i18n( get_option( 'date_format' ), strtotime( $order['date_create'] ) ); ?></span>
			</h3>
		<?php } ?>

		<h4><?php echo __( 'Product', BWS_BKNG_TEXT_DOMAIN ) . ':&nbsp;' . get_the_title( $product['id'] ); ?></h4>

		<?php if ( bws_bkng_show_product_price( $product['id'] ) ) { ?>

			<h4 class="bws_bkng_order_product_price">
				<?php echo __( "Price", BWS_BKNG_TEXT_DOMAIN ) . ':&nbsp;' . esc_html( bws_bkng_product_price( $product['id'] ) ); ?>
			</h4>

		<?php } ?>

		<h4><?php _e( "Pick-up drop-off dates", BWS_BKNG_TEXT_DOMAIN ); ?>:</h4>

		<div class="bws_bkng_product_rent_interval">
			<?php bws_bkng_datetimepicker_data( $product['rent_interval']['from'], $product['rent_interval']['till'] ); ?>
		</div>

		<h4>
			<?php _e( "Quantity", BWS_BKNG_TEXT_DOMAIN ); ?>:&nbsp;
			<span class="bws_bkng_order_product_quantity"><?php echo esc_html( bws_bkng_number_format( $product['quantity'], array( 'decimals' => 0 ) ) ); ?></span>
		</h4>

		<?php if ( $show_totals ) { ?>

			<h4><?php __( "Order Subtotal", BWS_BKNG_TEXT_DOMAIN ) . ':&nbsp;' . esc_html( bws_bkng_price_format( $product['total'], 'bws_bkng_product_subtotal', $price_format ) ); ?></h4>

		<?php } ?>

		<h4><?php _e( "Extras", BWS_BKNG_TEXT_DOMAIN ); ?>:</h4>

		<div class="bws_bkng_selected_extras">

			<?php if ( 1 < count( $order['products'] ) ) {
				foreach( $order['products'] as $id => $extra ) {

					if ( $product['id'] == $id )
						continue; ?>

					<div class="bws_bkng_ordered_extra_<?php echo esc_attr( $id ); ?>">
						<span class="bws_bkng_selected_extra_title"><?php echo esc_html( get_the_title( $id ) ); ?></span>
						<?php if ( bws_bkng_show_product_price( $id ) ) { ?>
							<span class="bws_bkng_price"><?php echo esc_html( bws_bkng_price_format( $extra['total'], 'bws_bkng_extra_total', $price_format ) ); ?></span>
						<?php } ?>
					</div>

				<?php }

			} else {
				_e( 'No chosen extras', BWS_BKNG_TEXT_DOMAIN );
			} ?>

		</div><!-- .bws_bkng_selected_extras -->

		<?php if ( $show_totals ) { ?>

			<h4><?php echo __( "Extras Subtotal", BWS_BKNG_TEXT_DOMAIN ) . ':&nbsp;' . esc_html( bws_bkng_price_format( $order['extras_total'], 'bws_bkng_extras_subtotal', $price_format ) ); ?></h4>

			<h4><?php echo __( "Subtotal", BWS_BKNG_TEXT_DOMAIN ) . ':&nbsp;' . esc_html( bws_bkng_price_format( $order['subtotal'], 'bws_bkng_order_subtotal', $price_format ) ); ?></h4>

			<h4 class="bws_bkng_order_total_wrap"><?php echo __( "Total", BWS_BKNG_TEXT_DOMAIN ) . ':&nbsp;' . esc_html( bws_bkng_price_format( $order['subtotal'], 'bws_bkng_order_total', $price_format ) ); ?></h4>

		<?php } ?>

	</div><!-- .bws_bkng_order_data -->

	<?php if ( $order_id ) {

		$billing_data = bws_bkng_get_billing_data( $order );

		if ( $billing_data ) { ?>

			<div class="bws_bkng_order_data bws_bkng_billing_details">

				<h4><?php _e( 'Billing Details', BWS_BKNG_TEXT_DOMAIN ); ?>:</h4>

				<?php do_action( 'bws_bkng_before_billing_details' ); ?>

				<p>
					<strong><?php _e( 'First Name', BWS_BKNG_TEXT_DOMAIN ); ?>:</strong>
					<span><?php echo esc_html( $billing_data['user_firstname'] ); ?></span>
				</p>

				<p>
					<strong><?php _e( 'Last Name', BWS_BKNG_TEXT_DOMAIN ); ?>:</strong>
					<span><?php echo esc_html( $billing_data['user_lastname'] ); ?></span>
				</p>

				<?php do_action( 'bws_bkng_after_personal_info', $billing_data ); ?>

				<p>
					<strong><?php _e( 'Phone', BWS_BKNG_TEXT_DOMAIN ); ?>:</strong>
					<span><?php echo esc_html( $billing_data['user_phone'] ); ?></span>
				</p>

				<p>
					<strong><?php _e( 'Email', BWS_BKNG_TEXT_DOMAIN ); ?>:</strong>
					<span><?php echo esc_html( $billing_data['user_email'] ); ?></span>
				</p>

				<p>
					<strong><?php _e( 'Message', BWS_BKNG_TEXT_DOMAIN ); ?>:</strong>
					<span><?php echo esc_html( $billing_data['user_message'] ); ?></span>
				</p>

				<?php do_action( 'bws_bkng_after_billing_details' ); ?>

			</div><!-- .bws_bkng_order_data -->

		<?php }
	}

	do_action( 'bws_bkng_after_order' );

} elseif ( ! $order_id ) {

	do_action( 'bws_bkng_no_order' );

	_e( 'There is nothing to order', BWS_BKNG_TEXT_DOMAIN );
}