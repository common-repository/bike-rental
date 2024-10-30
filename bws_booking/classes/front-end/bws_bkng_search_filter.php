<?php
/**
 * @uses     To generate the data before displaying it in the products search filter
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Search_Filter' ) )
	return;

class BWS_BKNG_Search_Filter {

	/**
	 * Contains an instance of the current class
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    object
	 */
	private static $instance;

	/**
	 * Contains the list of fields data are used in the search filter
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    array in the next format:
	 * 	array(
	 * 	'numerics' => array(
	 * 		'field_key' => array(
	 * 			'label' => {string},  - the field title
	 * 			'min'   => {number},  - the min. field range value
	 * 			'max'   => {number},  - the max. field range value
	 * 			'from'  => {number},  - the min. field current value
	 * 			'to'    => {number},  - the max. field current value
	 * 			'dec'   => {number},  - the number of decimals (range step)
	 * 			'measure' => {string} - the unit of measuremant
	 * 		);
	 * 		...
	 * 	),
	 * 	'lists' => array(
	 * 		'field_key' => array(
	 * 			'label'  => {string},  - the field title
	 * 			'type'   => {string},  - the field display type (select, list of checkboxes, list of radioboxes)
	 * 			'value'  => {mixed},   - the field current value - array|string|number
	 * 			'list'   => array(     - the list of fields items
	 * 				'[item_value]' => [item_label {string}]
	 * 				...
	 * 			)
	 * 		...
	 * 	)
	 * 	)
	 */
	private $fields;

	/**
	 * Fetch the class instance
	 * @since    0.1
	 * @access   public
	 * @static
	 * @param    void
	 * @return   object    An instance of the current class
	 */
	public static function get_instance() {

		if ( ! self::$instance instanceof self )
			self::$instance = new self();

		return self::$instance;
	}

	/**
	 * Fetch the list of fields data
	 * @since    0.1
	 * @access   public
	 * @static
	 * @param    void
	 * @return   array
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * Fetch the search query
	 * @since    0.1
	 * @access   private
	 * @static
	 * @param    array
	 * @return   array
	 */
	private function get_search_query( $query ) {

		if ( empty( $query['search'] ) )
			return array();

		$excess    = array( "per_page", "orderby", "order", "view" );
		$to_remove = array_intersect( $excess, array_keys( $query['search'] ) );

		if ( empty( $to_remove ) )
			return $query['search'];

		foreach( $to_remove as $key )
			unset( $query['search'][ $key ] );

		return $query['search'];

	}

	/**
	 * Class constructor
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   void
	 */
	private function __construct() {
		global $bws_bkng, $wpdb, $bws_search_form_filters;

		$query = bws_bkng_get_query();

		if ( empty( $query['bkng_category_id'] ) ) {
			$category = $bws_bkng->is_only_one_category();
			/**
			 * If there is only one category on site get its attributes
			 */
			if ( $category )
				$query['bkng_category_id'] = $category->term_id;
			//else
				//return;
		}

		$search_query = $this->get_search_query( $query );
		$this->fields = array( 'numerics' => array(), 'lists' => array() );

		if ( ! empty( $bws_search_form_filters ) && isset( $bws_search_form_filters[ $_GET['bws_bkng_post_type'] ] ) ) {
			foreach ( $bws_search_form_filters[ $_GET['bws_bkng_post_type'] ] as $filter_slug => $filter_title ) {
                $filter_title = sanitize_text_field( stripslashes( $filter_title ) );
				switch ( $filter_slug ) {
					case 'price':
					    $post_type = sanitize_text_field( stripslashes( $_GET['bws_bkng_post_type'] ) );
						$price_field_id = $wpdb->get_var(
						    $wpdb->prepare(
						        'SELECT `field_id` 
                                FROM `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_ids`
                                WHERE `field_slug`=%s',
                                $bws_bkng->plugin_prefix . '_price'
                            )
                        );
						$range = $wpdb->get_row(
						    $wpdb->prepare(
                                'SELECT MIN( CAST( `post_value` AS decimal ) ) AS `min`, MAX( CAST( `post_value` AS decimal ) ) AS `max`
                                FROM `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_post_data`
                                WHERE `field_id`=%d AND `post_id` IN (
                                    SELECT `ID` FROM `' . $wpdb->posts . '` WHERE `post_type`=%s
                                );',
                                $price_field_id,
                                $post_type
                            )
						);
						$min    = empty( $range->min ) ? apply_filters( 'bws_bkng_default_range_min', 0,    'price' ) : floatval( $range->min );
						$max    = empty( $range->max ) ? apply_filters( 'bws_bkng_default_range_max', 1000, 'price' ) : floatval( $range->max );
						$values = empty( $search_query['bws_bkng_price'] ) ? false : $bws_bkng->sanitize_number_range( $search_query['bws_bkng_price'] );

						$field_data            = array();
						$field_data['label']   = $filter_title;
						$field_data['min']     = $min;
						$field_data['max']     = $max;
						$field_data['from']    = empty( $values[0] ) || $values[0] < $min ? $min : $values[0];
						$field_data['to']      = empty( $values[1] ) || $values[1] > $max ? $max : $values[1];
						$field_data['dec']     = $bws_bkng->get_option( 'number_decimals' );
						$field_data['measure'] = $bws_bkng->get_option( 'currency' );

						/* Don't need to display the price slider range if all products have the same price */
						if ( $field_data['min'] != $field_data['max'] ) {
							$this->fields['numerics'][ 'price' ] = $field_data;
						}
						break;
					case 'rating':
						break;
					case 'accomodation':
						$bike_types = get_terms( array(
							'taxonomy'   => 'bike_type',
							'hide_empty' => true,
						));
						if ( ! empty( $bike_types ) ) {
							$field_data            = array();
							$field_data['type']    = 'select_checkbox';
							$field_data['value']   = isset( $_GET['bws_bkng_accomodation'] ) ? sanitize_text_field( stripslashes( $_GET['bws_bkng_accomodation'] ) ) : '';
							$field_data['label']   = $filter_title;
							$field_data['list']    = array();
							foreach( $bike_types as $bike_type ) {
								$field_data['list'][ $bike_type->slug ] = $bike_type->name;
							}
							$this->fields['lists']['accomodation'] = $field_data;
						}
						break;
				}
			}
		}
	}

	/**
	 * Some magic to avoid the creation of several instances of this class
	 * @since  0.1
	 */
	private function __clone()  {}
	private function __sleep()  {}
	private function __wakeup() {}
}
