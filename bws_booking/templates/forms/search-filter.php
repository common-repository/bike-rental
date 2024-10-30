<?php
/**
 * Display the search form by products attributes
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

$fields = bws_bkng_get_search_fields();

if ( empty( $fields ) )
	return;

do_action( 'bws_bkng_search_filter_before' );

bws_bkng_errors( 'filter' ); ?>

<form class="bws_bkng_search_products_form bws_bkng_search_filter" action="<?php echo esc_url( bws_bkng_get_page_permalink_by_post_type( 'products', esc_attr( $_GET['bws_bkng_post_type'] ) ) ); ?>" method="get">

	<div class="bws_bkng_filter_column bws_bkng_main_features">

		<div class="bws_bkng_filter_items">

			<?php if ( ! empty( $fields['numerics'] ) ) { ?>

				<div class="bws_bkng_filter_item bws_bkng_filter_numerics">

					<?php foreach( $fields['numerics'] as $key => $data )
						bws_bkng_numeric_range( $key, $data ); ?>

				</div><!-- .bws_bkng_filter_item bws_bkng_filter_numerics -->

			<?php }

			if ( ! empty( $fields['lists'] ) ) {
				foreach( $fields['lists'] as $key => $data ) { ?>
					<div class="bws_bkng_filter_item bws_bkng_filter_<?php echo esc_attr( $key ); ?>">
						<?php bws_bkng_items_list( $key, $data ); ?>
					</div><!-- .bws_bkng_filter_item bws_bkng_filter_<?php echo $key; ?> -->
				<?php }
			} ?>

		</div><!-- .bws_bkng_filter_items -->

	</div><!-- .bws_bkng_filter_column.bws_bkng_main_features -->

	<div class="bws_bkng_filter_column bws_bkng_filter_submit">

		<?php bws_bkng_submit_buttons();
		//bws_bkng_hidden_inputs(); ?>

	</div><!-- .bws_bkng_filter_column bws_bkng_filter_submit -->
	<?php $current_search_query = bws_bkng_get_query();
	if ( ! empty( $current_search_query['search'] ) ) {
		global $bws_bkng, $bws_search_form_filters;
		$filter_field_array = array_keys( $bws_search_form_filters[ sanitize_text_field( stripslashes( $_GET['bws_bkng_post_type'] ) ) ] );
		foreach ( $current_search_query['search'] as $key => $value ) {
			if ( in_array( str_replace( 'bws_bkng_', '', $key ), $filter_field_array ) ) {
				continue;
			}
			echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
		}
	} ?>

</form><!-- .bws_bkng_search_filter -->
<?php do_action( 'bws_bkng_search_filter_after' );
