<?php /**
 * Contains functions which are used in site front-end.
 * Relative to the Agencies pages
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

/**
 * Fetch the agency data
 * @since    0.1
 * @param    void
 * @return   array
 */
if ( ! function_exists('bws_bkng_get_agencies_data' ) ) {
	function bws_bkng_get_agencies_data() {
		global $bws_bkng;

		return $bws_bkng->get_terms( BWS_BKNG_AGENCIES, array( 'hide_empty' => false ) );
	}
}

/**
 * Fetch the agency wrapper HTML attribute 'class' value
 * @since    0.1
 * @param    void
 * @return   string
 */
if ( ! function_exists( 'bws_bkng_agencies_class' ) ) {
	function bws_bkng_agencies_class() {

		$classes = array( 'bws_bkng_agencies_list' );

		$classes = join( ' ', apply_filters( 'bws_bkng_agencies_class', $classes ) );
		echo esc_attr( "class=\"{$classes}\"" );
	}
}

/**
 * Displays additional HTML attributes for each agency
 * @since    0.1
 * @param    string    $slug - agency's slug from $agency array ( $agency->slug )
 * @return   string
 */
if ( ! function_exists( 'bws_bkng_agency_classes' ) ) {
	function bws_bkng_agency_classes( $slug ) {
		global $bws_bkng;

		$attributes = 'class="%1$s"';

		$classes = array( 'bws_bkng_agency', "bws_bkng_agency_" . esc_attr( $slug ) );
		$classes = join( ' ', apply_filters( 'bws_bkng_agency_class', $classes ) );

		printf( $attributes, $classes );
	}
}

/**
 * Displays the table with agency's work schedule
 * @since    0.1
 * @param    array    $agency        The currently managed agency
 * @return   void
 */
if ( ! function_exists( 'bws_bkng_agency_working_hours' ) ) {
	function bws_bkng_agency_working_hours( $agency ) {
		global $bws_bkng;

		if ( ! $agency instanceof WP_Term )
			return;

		$working_hours = maybe_unserialize( get_term_meta( $agency->term_id, 'bkng_agency_working_hours', true ) );

		if ( empty( $working_hours ) )
			return;

		$key  = get_option( 'start_of_week' );
		$week = $bws_bkng->get_week_days( apply_filters( 'bws_bkng_short_week_days', true ) );

		for ( $i = 0; $i < 7; $i++ ) { ?>

			<div class="bws_bkng_agency_workhours_day">

				<span class="bws_bkng_agency_workhours_week_day"><?php _e( $week[ $key ] ) ?></span>

				<?php if( $working_hours[ $key ]['holiday'] ) { ?>

					<span class="bws_bkng_agency_workhours_holiday">
						<?php _e( 'Holiday', BWS_BKNG_TEXT_DOMAIN ) ?>
					</span>

				<?php } else { ?>

					<span class="bws_bkng_agency_workhours_week_days_from_till">
						<?php echo esc_html( "{$working_hours[ $key ]["work_from"]} - {$working_hours[ $key ]["work_till"]}" ); ?>
					</span>

				<?php } ?>

			</div><!-- .bws_bkng_agency_workhours_day -->

			<?php $key = 6 <= $key ? 0 : $key + 1;
		}

		if( ! empty( $working_hours["notes"] ) ) { ?>
			<div class="bws_bkng_agency_workhours_note">
				<span class="bws_bkng_agency_workhours_note_title"><?php _e( 'Note', BWS_BKNG_TEXT_DOMAIN ); ?>:&nbsp;</span>
				<span class="bws_bkng_agency_workhours_note_text"><?php echo esc_html( $working_hours["notes"] ); ?></span>
			</div>
		<?php }
	}
}

/**
 * Displays the agency phone number
 * @since    0.1
 * @param    object  The instance of WP_TERM class
 * @return   array
 */
if ( ! function_exists( 'bws_bkng_agency_phone' ) ) {
	function bws_bkng_agency_phone( $agency ) {

		if ( ! $agency instanceof WP_Term )
			return;

		$phone = get_term_meta( $agency->term_id, 'bkng_agency_phone', true );

		if ( empty( $phone ) )
			return; ?>

		<span class="bws_bkng_agency_phone_number"><?php echo esc_html( $phone ); ?></span>

	<?php }
}

/**
 * Displays map with the agency location data
 * @since    0.1
 * @param    object  The instance of WP_TERM class
 * @return   array
 */
if ( ! function_exists( 'bws_bkng_agency_location' ) ) {
	function bws_bkng_agency_location( $agency ) {

		if ( ! $agency instanceof WP_Term )
			return;

		$location  = get_term_meta( $agency->term_taxonomy_id, 'bkng_term_address', true );
		$longitude = get_term_meta( $agency->term_taxonomy_id, 'bkng_term_longitude', true );
		$latitude  = get_term_meta( $agency->term_taxonomy_id, 'bkng_term_latitude', true );
		$data      = array(
			'label' 	=> '',
			'address' 	=> $location,
			'longitude' => $longitude,
			'latitude' 	=> $latitude,
			'id'       	=> 'bws_bkng_agency_' . $agency->slug,
		);
		bws_bkng_get_map_data( $data );
	}
}

/**
 * Fetch the current agency data
 * @since  0.1
 * @param  boolean  $force_get    If it is set to true, allows to get the agency data not depending on
 *                                the $bws_bkng_agency value
 * @return object|false           The instance of WP_term class if the agency data were founded,
 *                                false otherwise
 */
if ( ! function_exists( 'bws_bkng_get_agency_data' ) ) {
	function bws_bkng_get_agency_data( $force_get = false ) {
		global $bws_bkng_agency;

		if ( $bws_bkng_agency instanceof WP_Term && ! $force_get )
			return $bws_bkng_agency;

		if ( get_option( 'permalink_structure' ) )
			$agency = get_query_var( BWS_BKNG_AGENCIES );
		elseif ( isset( $_GET['agency'] ) )
			$agency = sanitize_key( $_GET['agency'] );

		if ( empty( $agency ) )
			return false;

		$bws_bkng_agency = get_term_by( 'slug', $agency, BWS_BKNG_AGENCIES );

		return $bws_bkng_agency;
	}
}

/**
 * Checks whether to display the agency meta data.
 * @since    0.1
 * @param    string    $field     The meta field slug
 * @return   boolean
 */
if ( ! function_exists( 'bws_bkng_display_agency_meta' ) ) {
	function bws_bkng_display_agency_meta( $field ) {
		global $bws_bkng;

		$meta_fields = $bws_bkng->get_option( 'agencies_additional_meta' );

		return array_key_exists( $field, $meta_fields ) && $meta_fields[ $field ];
	}
}