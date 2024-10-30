<?php
/**
 * Displays the list of products categories available for primary search
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

$categories = bws_bkng_get_categories();

if ( empty( $categories ) ) {

	bws_bkng_get_template_part( 'none' );

} else { ?>

	<h3><?php _e( 'Categories', BWS_BKNG_TEXT_DOMAIN ); ?>:</h3>

	<?php do_action( 'bws_bkng_before_categories_list' ); ?>

	<div <?php bws_bkng_products_list_class(); ?>>

		<?php foreach ( $categories as $category ) { ?>
			<div <?php bws_bkng_category_classes( $category->slug ) ?>>

				<div class="bws_bkng_product_head">
					<?php bws_bkng_category_thumbnail( $category ); ?>
				</div><!-- bws_bkng_product_head -->

				<div class="bws_bkng_product_content">
					<h3 class="bws_bkng_product_title"><a href="<?php echo esc_url( get_term_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a></h3>
				</div><!-- .bws_bkng_product_content -->

			</div><!-- .bws_bkng_category -->

		<?php } ?>

	</div><!-- .bws_bkng_products_list -->

	<?php do_action( 'bws_bkng_after_categories_list' );
} ?>
