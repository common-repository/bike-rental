<?php
/**
 * Display the single agency data
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

$agency = bws_bkng_get_agency_data();

if ( empty( $agency ) ) {

	bws_bkng_get_template_part( 'none' );

} else {

	do_action( 'bws_bkng_before_agency', $agency ); ?>

	<div <?php bws_bkng_agency_classes( $agency->slug ); ?>>

		<?php if ( bws_bkng_display_agency_meta( 'featured_image' ) ) { ?>
			<div class="bws_bkng_thumbnail_column">
				<?php bws_bkng_category_thumbnail( $agency ); ?>
			</div>
		<?php } ?>

		<h3 class="bws_bkng_agency_title"><?php echo esc_html( $agency->name ); ?></h3>

		<?php if ( ! empty( $agency->description ) ) { ?>
			<div class="bws_bkng_agency_description"><?php echo esc_html( $agency->description ); ?></div>
		<?php }

		if ( bws_bkng_display_agency_meta( 'location' ) ) { ?>
			<div class="bws_bkng_agency_location">
				<h4><?php _e( 'Location', BWS_BKNG_TEXT_DOMAIN ); ?></h4>
				<?php bws_bkng_agency_location( $agency ); ?>
			</div>
		<?php }

		if ( bws_bkng_display_agency_meta( 'working_hours' ) ) { ?>
			<div class="bws_bkng_agency_workhours">
				<h4><?php _e( 'Time schedule', BWS_BKNG_TEXT_DOMAIN ); ?></h4>
				<?php bws_bkng_agency_working_hours( $agency ); ?>
			</div><!-- bws_bkng_agency_workhours -->
		<?php }

		if ( bws_bkng_display_agency_meta( 'phone' ) ) { ?>
			<div class="bws_bkng_agency_phone">
				<h4><?php _e( 'Phone number', BWS_BKNG_TEXT_DOMAIN ); ?></h4>
				<?php bws_bkng_agency_phone( $agency ); ?>
			</div>
		<?php }

		if ( bws_bkng_display_agency_meta( 'image_gallery' ) ) { ?>
			<div class="bws_bkng_agency_gallery">
				<h4><?php _e( 'Gallery', BWS_BKNG_TEXT_DOMAIN ); ?></h4>
				<?php bws_bkng_get_template_part( 'gallery' ); ?>
			</div>
		<?php } ?>
	</div><!-- bws_bkng_agency_classes -->

	<?php do_action( 'bws_bkng_after_agency', $agency );
}
