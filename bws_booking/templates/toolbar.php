<?php
/**
 * Displays the block of items in order to sort products list
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

$query = bws_bkng_get_query();

do_action( 'bws_bkng_before_toolbar' ); ?>

<div class="bws_bkng_toolbar" action="" method="post">

	<div class="bws_bkng_tool bws_bkng_tool_per_page">
		<span><?php _e( 'Per page', BWS_BKNG_TEXT_DOMAIN ); ?>:</span>
		<?php /* add default WP 'per_page'- option to the list */
		$wp_default = absint( get_option( 'posts_per_page' ) );
		$numbers = array(
			5   => 5,
			10  => 10,
			25  => 25,
			50  => 50,
			100 => 100,
			$wp_default => $wp_default
		);
		asort( $numbers );
		$current = empty( $query['posts_per_page'] ) ? 10 : absint( $query['posts_per_page'] );
		bws_bkng_link_list( $numbers, $current, 'show' ); ?>
	</div><!-- .bws_bkng_tool.bws_bkng_tool_per_page -->

	<div class="bws_bkng_tool bws_bkng_tool_order_by">
		<span><?php _e( 'Sort by', BWS_BKNG_TEXT_DOMAIN ); ?>:</span>
		<?php $orderby_fields = bws_bkng_get_orders_fields();
		$current = 'meta_value_num' == $query['orderby'] ? 'price' : $query['orderby'];
		bws_bkng_link_list( $orderby_fields, $current, 'orderby' ); ?>
	</div><!-- .bws_bkng_tool.bws_bkng_tool_order_by -->

	<div class="bws_bkng_tool bws_bkng_tool_order">
		<span><?php _e( 'Sort', BWS_BKNG_TEXT_DOMAIN ); ?>:</span>
		<?php $order_fields = array(
			'asc'  => __( 'Asc', BWS_BKNG_TEXT_DOMAIN ),
			'desc' => __( 'Desc', BWS_BKNG_TEXT_DOMAIN )
		);
		bws_bkng_link_list( $order_fields, $query['order'], 'order' ); ?>
	</div><!-- .bws_bkng_tool.bws_bkng_tool_order -->

	<div class="bws_bkng_tool bws_bkng_tool_view">
		<?php bws_bkng_views_list(); ?>
	</div><!-- .bws_bkng_tool.bws_bkng_tool_view -->

</div><!-- .bws_bkng_toolbar -->


<?php do_action( 'bws_bkng_after_toolbar' );