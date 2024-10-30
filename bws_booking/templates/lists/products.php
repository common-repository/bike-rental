<?php
/**
 * Displays the list of products
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

$query_posts = bws_bkng_query_products();

if ( $query_posts && have_posts() ) {

	$is_list_view = bws_bkng_is_list_view();

	bws_bkng_get_template_part( 'toolbar' );

	do_action( 'bws_bkng_before_products_list' ); ?>

	<div <?php bws_bkng_products_list_class(); ?>>

		<?php while ( have_posts() ) {

			the_post(); ?>

			<div <?php bws_bkng_product_classes(); ?>>

				<?php if ( $is_list_view ) { ?>
					<div class="bws_bkng_column bws_bkng_thumbnail_column">
				<?php } ?>

					<div class="bws_bkng_product_head">
						<?php the_post_thumbnail( 'bkng_catalog_' . get_post_type() . '_image' ); ?>
					</div><!-- .bws_bkng_product_head -->

				<?php if ( $is_list_view ) { ?>
					</div><!-- .bws_bkng_column.bws_bkng_thumbnail_column -->

					<div class="bws_bkng_column bws_bkng_content_column">
				<?php } ?>

					<div class="bws_bkng_product_content">

						<h3 class="bws_bkng_product_title"><?php the_title(); ?></h3>

					</div><!-- .bws_bkng_product_content -->

					<div class="bws_bkng_product_footer">

						<?php if ( 'available' == bws_bkng_get_product_status() ) { ?>
							<a href="<?php the_permalink(); ?>"><?php _e( 'Book now', BWS_BKNG_TEXT_DOMAIN ); ?></a>
						<?php } else {
							bws_bkng_product_status_notice();
						}

						bws_bkng_get_template_part( 'price' );

						do_action( 'bws_bkng_product_footer_bottom' ); ?>
					</div><!-- .bws_bkng_product_foot-->

				<?php if ( $is_list_view ) { ?>
					</div><!-- .bws_bkng_column.bws_bkng_content_column -->
				<?php } ?>

			</div><!-- .bws_bkng_product -->

		<?php } ?>

	</div><!-- .bws_bkng_products_list -->

	<?php do_action( 'bws_bkng_after_products_list' );

	the_posts_pagination( array(
		'prev_text' => '&larr;',
		'next_text' => '&rarr;',
		'mid_size'  => 1
	) );

} else {

	bws_bkng_get_template_part( 'none' );

}

if ( ! empty( $query_posts ) )
	wp_reset_query();

