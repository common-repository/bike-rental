<?php
/**
 * @uses     To handle the data for Agencies and location type attributes
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Locations_Metabox' ) ) {
	return;
}

class BWS_BKNG_Locations_Metabox extends BWS_BKNG_Term_Metabox {

    /**
     * Class constructor
     * @param void
     * @param string $child
     * @since  0.1
     * @access public
     */
	public function __construct( $taxonomy, $child = '' ) {
		$class = empty( $child ) ? __CLASS__ : $child;
		parent::__construct( $taxonomy, $class );
	}

	/**
	 * Fetch the list of metaboxes to be displayed on the edit term page
	 * @see    BWS_BKNG_Term_Metabox::get_items()
	 * @since  0.1
	 * @access public
	 * @param    object|string $tag              An instance of the WP_TERM class the taxonomy slug otherwise
	 * @param    string        $tax_slug         The term taxonomy slug
	 * @param    boolean       $is_edit_page     Whether the metabox will be displayed on the single term edit page or on the add-new-term page
	 * @return array
	 */
	public function get_items( $tag, $tax_slug = null, $is_edit_page ) {
		return array(
			'location' => array(
				'label'   => __( 'Location', BWS_BKNG_TEXT_DOMAIN ),
				'content' => self::get_location_metabox( $tag )
			)
		);
	}

	/**
	 * Save the term data
	 * @see    BWS_BKNG_Term_Metabox::save_term_data()
	 * @since  0.1
	 * @access public
	 * @param  int        $term_id      The term ID
	 * @param  int        $tt_id        The term taxonomy ID
	 * @param  string     $tax_slug     The term taxonomy slug
	 * @return void
	 */
	public function save_term_data( $term_id, $tt_id, $tax_slug = '' ) {
		global $bws_bkng;

		if ( empty( $_POST['bkng_term'] ) || ! is_array( $_POST['bkng_term'] ) )
			return;

		$defaults = array(
			'address'   => $bws_bkng->get_option( 'google_map_default_address' ),
			'latitude'  => $bws_bkng->get_option( 'google_map_default_lat' ),
			'longitude' => $bws_bkng->get_option( 'google_map_default_lng' )
		);
		$data = array();

		/**
		 * Sanitize term data
		 */
		foreach( $_POST['bkng_term'] as $key => $value ) {
			if ( ! array_key_exists( $key , $defaults ) )
				continue;

			$data[ $key ] = in_array( $key, array( 'latitude', 'longitude' ) ) ? floatval( $value ) : sanitize_text_field( stripslashes( $value ) );
		}

		/**
		 * Save term data
		 */
		foreach( $data as $key => $value )
			update_term_meta( $term_id, "bkng_term_{$key}", $value );
	}

	/**
	 * Returns the content of Location metabox
     * @param object|string $tag
	 * @since  0.1
	 * @access public
	 * @static
	 * @return string
	 */
	public static function get_location_metabox( $tag ) {
		global $bws_bkng;
		add_action( 'admin_footer', array( $bws_bkng, 'add_google_map_scripts' ) );
		$wrap =
			'<div id="bkng_edit_term_location" class="bkng_meta_input_wrap">
				<!-- notice for google maps -->
				<noscript>
					<div class="error notice bkng_error_wrap">
						<p>' . __( 'Please enable JavaScript to add new location', BWS_BKNG_TEXT_DOMAIN ) . '.</p>
					</div>
				</noscript>
				<div>%1$s</div>
				<div>%2$s</div>
				<div id="bkng_map_wrap" class="bkng_map_wrap hide-if-no-js"></div>
				%3$s
				<div>%4$s</div>
				<div>%5$s</div>
			</div>';
		$before = __( 'Address', BWS_BKNG_TEXT_DOMAIN );
		$class  = "bkng_address_input";
		$name   = "bkng_term[address]";
		$value  = empty( $tag->term_id ) ? $bws_bkng->get_option( 'google_map_default_address' ) : get_term_meta( $tag->term_id, "bkng_term_address", true );
		$address = $bws_bkng->get_text_input( compact( 'before', 'class', 'name', 'value' ) );

		$unit = 'button';
		$class = "button bkng_find_by_address_button hide-if-no-js";
		$value = __( 'Find by address', BWS_BKNG_TEXT_DOMAIN );
		$find_by_address = $bws_bkng->get_form_unit( compact( 'unit', 'class', 'value' ) );

		$errors = $bws_bkng->get_errors( '', '', "inline bkng_find_by_coors_error " . BWS_BKNG::$hidden );

		$unit = 'button';
		$class = "button bkng_find_by_coordinates_button hide-if-no-js";
		$value = __( 'Find by coordinates', BWS_BKNG_TEXT_DOMAIN );
		$find_by_coors = $bws_bkng->get_form_unit( compact( 'unit', 'class', 'value' ) );

		$text_fields_names = array(
			'latitude' => array( __( 'Latitude', BWS_BKNG_TEXT_DOMAIN ), $bws_bkng->get_option( 'google_map_default_lat' ) ),
			'longitude' => array( __( 'Longitude', BWS_BKNG_TEXT_DOMAIN ), $bws_bkng->get_option( 'google_map_default_lng' ) )
		);
		$coors = '';
		foreach ( $text_fields_names as $field_name => $data ) {
			$before = $data[0];
			$class  = "bkng_{$field_name}_input";
			$name   = "bkng_term[{$field_name}]";
			$value  = empty( $tag->term_id ) ? $data[1] : get_term_meta( $tag->term_id, "bkng_term_{$field_name}", true );
			$coors .= $bws_bkng->get_text_input( compact( 'before', 'class', 'name', 'value' ) );
		}
		return sprintf( $wrap, $address, $find_by_address, $errors, $find_by_coors, $coors );
	}
}