<?php
/**
 * Displays the list of registered agencies
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

$agencies = bws_bkng_get_agencies_data();

if ( empty( $agencies ) ) {

	bws_bkng_get_template_part( 'none' );

} else { ?>

	<?php do_action( 'bws_bkng_before_agencies_list' );  ?>

	<div <?php bws_bkng_agencies_class(); ?>>

		<?php foreach ( $agencies as $agency ) { ?>

			<div <?php bws_bkng_agency_classes( $agency->slug ); ?>>

				<?php if ( bws_bkng_display_agency_meta( 'featured_image' ) ) { ?>
					<div class="bws_bkng_column bws_bkng_thumbnail_column">

						<?php do_action( 'bws_bkng_before_agency_thumbnail', $agency ); ?>

						<a class="bws_bkng_agency_thumbnail" href="<?php echo esc_url( get_term_link( $agency ) ); ?>"><?php bws_bkng_category_thumbnail( $agency ); ?></a>

						<?php do_action( 'bws_bkng_after_agency_thumbnail', $agency ); ?>

					</div><!-- .bws_bkng_column.bws_bkng_thumbnail_column-->
				<?php } ?>

				<div class="bws_bkng_column bws_bkng_content_column">

					<?php do_action( 'bws_bkng_before_agency_title' ); ?>

					<h4 class="bws_bkng_agency_title">
						<a href="<?php echo esc_url( get_term_link( $agency ) ); ?>"><?php echo esc_html( $agency->name ); ?></a>
					</h4>

					<?php do_action( 'bws_bkng_after_agency_title' );

					if ( bws_bkng_display_agency_meta( 'location' ) ) { ?>
						<h5><?php _e( 'Our Address', BWS_BKNG_TEXT_DOMAIN ); ?></h5>
						<div class="bws_bkng_agency_location">
							<?php bws_bkng_agency_location( $agency ); ?>
						</div>
					<?php }

					if ( bws_bkng_display_agency_meta( 'working_hours' ) ) { ?>

						<div class="bws_bkng_agency_workhours">
							<h5><?php _e( 'Time schedule', BWS_BKNG_TEXT_DOMAIN ); ?></h5>
							<?php bws_bkng_agency_working_hours( $agency ); ?>
						</div><!-- bws_bkng_agency_workhours -->
					<?php }

					if( bws_bkng_display_agency_meta( 'phone' ) ) { ?>
						<div class="bws_bkng_agency_phone">
							<?php bws_bkng_agency_phone( $agency ); ?>
						</div>
					<?php } ?>

				</div><!-- .bws_bkng_column.bws_bkng_content_column -->
			</div><!-- bws_bkng_agency_classes -->
		<?php } ?>
	</div><!-- bws_bkng_agencies_class -->

	<?php do_action( 'bws_bkng_after_agencies_list' );
} ?>
