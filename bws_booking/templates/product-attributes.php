<?php
/**
 * Displays th list of the product attributes
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

$renderer = BWS_BKNG_Post_Meta::get_instance();
$meta     = $renderer->get_meta();

if ( empty( $meta ) ) {

	do_action( 'bws_bkng_no_meta' );

} else {

	do_action( 'bws_bkng_before_meta', $meta );

	foreach( $meta as $priority_key => $display_data ) { ?>
		<div class="bws_bkng_post_meta bws_bkng_post_meta_<?php echo esc_attr( $priority_key ); ?>">
			<?php if ( ! empty( $display_data ) ) {
				foreach ( $display_data as $key => $data ) { ?>
					<div class="bws_bkng_meta_row <?php echo esc_attr( $key ); ?>">
						<?php /* check whether the curently managed attribute is location-type */
						if ( array_key_exists( 'longitude', $data ) ) {
							bws_bkng_get_map_data( array_merge( $data, array( 'id' => $key ) ) );
						} else { ?>
							<div class="bws_bkng_column bkng_meta_label"><?php echo esc_html( $data['label'] ); ?>:&nbsp;</div>
							<div class="bws_bkng_column bkng_meta_value"><?php echo esc_html( $data['value'] ); ?></div>
						<?php } ?>
					</div><!-- .bws_bkng_meta_row -->
				<?php }
			} ?>
		</div><!-- .bws_bkng_post_meta -->
	<?php }

	do_action( 'bws_bkng_after_meta', $meta );
}


