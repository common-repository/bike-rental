
<?php
/**
 * Displays the primary search form by product categories
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

$categories = bws_bkng_get_categories();

if ( empty( $categories ) ) {

	bws_bkng_get_template_part( 'none' );

} else {

	bws_bkng_errors( 'search_products' );

	do_action( 'bws_bkng_before_search_products' ); ?>

	<form class="bws_bkng_search_products_form bws_bkng_search_primary" action="<?php echo esc_url( bws_bkng_get_page_permalink( 'products' ) ); ?>" method="get">

		<?php do_action( 'bws_bkng_search_products_before_items' ); ?>

		<div class="bws_bkng_search_products_item bws_bkng_search_products_datepicker">
			<?php bws_bkng_datetimepicker_form(); ?>
		</div><!-- .bws_bkng_products_item bws_bkng_products_date -->

		<div class="bws_bkng_search_products_item bws_bkng_search_products_categories">
			<?php bws_bkng_categories_list(); ?>
		</div><!-- .bws_bkng_products_item bws_bkng_search_products_categories -->

		<?php do_action( 'bws_bkng_search_products_after_items' ); ?>
		
		<div class="bws_bkng_buttons_item">
			<?php bws_bkng_submit_buttons(); ?>
		</div>

	</form><!-- .bws_bkng_search_products -->

	<?php do_action( 'bws_bkng_after_search_products' );

}
