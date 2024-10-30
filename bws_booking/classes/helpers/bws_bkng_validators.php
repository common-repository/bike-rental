<?php
/**
 * Contains methods for data validation
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Validator' ) )
	return;

class BWS_BKNG_Validators {

	/**
	 * Checks if the Dashboard or the administration panel is attempting to be displayed.
	 * @uses instead of WP core is_admin() tag due to the fact that WP is_admin()
	 * returns true during handling AJAX-request and sometimes it causes some incorrect data handling.
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return boolean|object    An object of WP_Term, false otherwise
	 */
	public function is_admin() {
		return is_admin() && ! defined( 'DOING_AJAX' );
	}

	/**
	 * Checks whether there is only one registered products category
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return boolean|object    An object of WP_Term, false otherwise
	 */
	public function is_only_one_category() {
		/*global $bws_bkng;
		$terms = $bws_bkng->get_terms(
			BWS_BKNG_CATEGORIES,
			array(
				'hide_empty' => false,
				'meta_query' => array(
					array(
						'key'     => 'bkng_exclude_from_search',
						'value'   => 1,
						'compare' => '!='
					)
				)
			)
		);

		return 1 == count( $terms ) ? $terms[0] : false;*/
		return false;
	}

	/**
	 * Checks whether the currently handled string is the date/time format string.
	 * @since  0.1
	 * @access public
	 * @param  string
	 * @return boolean
	 */
	public function is_valid_date_format( $format ) {
		try {
			$d = new DateTime( date( $format ) );
			return $d->format( $format );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Checks whether the currently handled string is the date.
	 * @since  0.1
	 * @access public
	 * @param  string
	 * @return boolean
	 */
	public function is_valid_date( $date ) {
		try {
			return !! strtotime( $date );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Checks whether the currently handled string is the time.
	 * @since  0.1
	 * @access public
	 * @param  string
	 * @return boolean
	 */
	public function is_valid_time( $time ) {
		$pattern = apply_filters( 'bws_bkng_time_pattern', '/^(0{0,1}\d|1\d|2[0-3])[\s]?:[\s]?[0-5]\d(([\s]?:[\s]?[0-5]\d)|([\s]?(am|pm)))?$/i' );
		return !! preg_match( $pattern, $time );
	}

	/**
	 * Checks whether the currently handled string is the phone number.
	 * @since  0.1
	 * @access public
	 * @param  string
	 * @return boolean
	 */
	public function is_valid_phone( $phone ) {
		$pattern = apply_filters( 'bws_bkng_phone_pattern', '/^[\+]{0,1}\d{0,1}[\s\-]{0,1}\d{0,1}[\s\-]{0,1}[[\(\s-]{0,2}\d{1,4}[\)\s-]{0,2}]{0,1}\d{1,3}[\s-]{0,1}\d{1,3}[\s-]{0,1}\d{1,3}$/i' );
		return !! preg_match( $pattern, $phone );
	}

	/**
	 * Checks whether the currently handled attribute is list-type attribute.
	 * @since  0.1
	 * @access public
	 * @param  string
	 * @return boolean
	 */
	public function is_taxonomy( $type = '' ) {
		return in_array( $type, array( 'select_checkboxes', 'select_radio', 'select', 'select_locations' ) );
	}

	/**
	 * Checks whether the currently handled attribute is location-type attribute.
	 * @since  0.1
	 * @access public
	 * @param  string
	 * @return boolean
	 */
	public function is_location( $type = '' ) {
		return 'location' === $type;
	}

	/**
	 * Checks if the currently called function is called from within the 'get_option' callback
	 * @since  0.1
	 * @access public
	 * @uses to prevent unauthorized reset of already saved plugin settings
	 * @see BWS_BKNG_Data_Loader::add_default_option(), BWS_BKNG_Data_Loader::upgrade_option(),
	 *      get_option(), update_option()
	 * @param  void
	 * @return boolean
	 */
	public function is_get_option_call() {
		global $bws_bkng;
		$functions = $bws_bkng->array_map( 'array_column', debug_backtrace(), 'function' );
		return in_array( 'get_option', $functions );
	}

	/**
	 * Checks whether the loaded theme template is basic
	 * @see https://wphierarchy.com/, https://developer.wordpress.org/themes/basics/template-hierarchy/
	 * @since  0.1
	 * @access public
	 * @param  string         $template_name    The template filename without '.php' extension.
	 * @param  string|array   $base             The list of basic template names
	 *                                          to be compared with the currently checked and which can
	 *                                          potentially be used to display the requested content.
	 * @return boolean
	 */
	public function is_primary_template( $template_name, $base ) {
		$primary_templates = array_merge( array( 'index' ), (array)$base );
		return in_array( $template_name, $primary_templates );
	}

	/**
	 * Checks whether the currently requested page is the products' taxonomie archive.
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return boolean
	 */
	public function is_booking_archive() {
		global $bws_bkng;

		return is_tax() && in_array( get_post_type(), $bws_bkng->get_post_types() );
	}

	/**
	 * Checks whether the currently requested page is the single product page.
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return boolean
	 */
	public function is_booking_post() {
		global $bws_bkng;
		return is_single() && in_array( get_post_type(), $bws_bkng->get_post_types() );
	}

	/**
	 * Checks whether the currently requested page is one of the plugin pages.
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return string|boolean    The page alias(slug), false otherwise
	 */
	public function is_booking_page() {
		global $bws_bkng;

		$post = get_post();

		if( empty( $post->ID ) || empty( $post->post_type ) || 'page' !== $post->post_type )
			return false;
			

		$option = $bws_bkng->get_post_type_option( $post->post_type );

		switch( $post->ID ) {
			case $option['products_page']:
				return 'products';
			case $option['checkout_page']:
				return 'checkout';
			case $option['thank_you_page']:
				return 'thank_you';
			case $option['agencies_page']:
				return 'agencies';

			/**
			 * Commented due to the fact that the functionality of such pages is not over yet.
			 * @todo Uncomment when it will be done
			 */
			// case $option['cart_page']:
			// 	return 'cart';
			// case $option['user_account_page']:
			// 	return 'user_account';
			// case $option['agents_page']:
			// 	return 'agents';
			default:
				return apply_filters( "bws_bkng_is_booking_page", false, $post );
		}
	}

}