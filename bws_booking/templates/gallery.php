<?php
/**
 * Displays the list of images attached to a product
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

$images = bws_bkng_query_gallery();

if ( empty( $images ) ) {

	do_action( 'bws_bkng_no_gallery' );

} else { ?>

	<?php do_action( 'bws_bkng_before_gallery' ); ?>

	<div class="bws_bkng_gallery">

		<?php foreach( $images as $id ) {

			$thumbnail_image = wp_get_attachment_image( $id, "bkng_thumbnails_image" );
			$full_size_image = wp_get_attachment_image_src( $id, "full" ); ?>

			<a data-fancybox="bkng_gallery_<?php echo esc_attr( get_the_ID() ); ?>" class="bws_bkng_product_thumbnail" href="<?php echo esc_url( $full_size_image[0] ) ?: get_default_image_src(); ?>" target="_blank">
				<?php echo $thumbnail_image ?: get_default_image(); ?>
			</a>

		<?php } ?>

	</div><!-- .bws_bkng_gallery -->

	<?php do_action( 'bws_bkng_after_gallery' );
}
