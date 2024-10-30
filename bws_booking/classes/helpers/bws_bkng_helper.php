<?php
/**
 * Contains methods are used in the plugin for different purposes
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Helper' ) )
	return false;

class BWS_BKNG_Helper {

	/**
	 * Fecth the list of post type slugs are used in the plugin
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return array
	 */
	public function get_post_types() {
		global $bws_bkng, $bws_post_type;
		$post_types = array_keys( $bws_post_type );

		if ( $bws_bkng->allow_variations )
			$post_types[] = BWS_BKNG_VARIATION;

		return $post_types;
	}

	/**
	 * Sanitize the parameters of numeric queries
	 * @since    0.1
	 * @access   public
	 * @param    string                $query     The query parameter's value
	 * @return   string/array/false               A string or array - if queried parameter is OK, false otherwise
	 */
	public function sanitize_number_range( $range ) {
		$raw = explode( '-', str_replace( ',', '', $range ) );

		$options = array(
			'flags' => array(
				FILTER_FLAG_ALLOW_FRACTION, FILTER_FLAG_ALLOW_THOUSAND
			),
		);

		if ( 1 == count( $raw ) )
			return filter_var( $raw[0], FILTER_VALIDATE_FLOAT, $options );

		$numbers = array();

		foreach ( $raw as $number )
			$numbers[] = filter_var( $number, FILTER_VALIDATE_FLOAT, $options );

		if ( empty( $numbers ) )
			return false;
		elseif( 1 == count( $numbers ) || $numbers[0] == $numbers[1] )
			return reset( $numbers );

		asort( $numbers );

		return array( $numbers[0], end( $numbers ) );
	}

	/**
	 * Fecth the currently requested page full URL
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return string
	 */
	public function get_current_url() {
		return ( is_ssl() ? 'https' : 'http' ) . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
	}

	/**
	 * Fecth the products list URL in the Dashboard
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return string
	 */
	public function get_post_type_page_url() {
		return "edit.php?post_type=" . BWS_BKNG_POST;
	}

	/**
	 * Fecth the list of terms of the given taxonomy.
	 * The need of this method is due to the difference in use of the function get_terms(),
	 * depending on the WP core version {@see https://developer.wordpress.org/reference/functions/get_terms/}.
	 * @since  0.1
	 * @access public
	 * @param  string     $taxonomy
	 * @return string
	 */
	public function get_terms( $taxonomy, $args = array() ) {
		global $wp_version;

		return
				version_compare( $wp_version, '4.5.0', '>=' )
			?
				get_terms( array_merge( $args, array( 'taxonomy' => $taxonomy ) ) )
			:
				get_terms( $taxonomy, $args );
	}

	/**
	 * Fetch the category's data (list of properties) of the given product.
	 * @since  0.1
	 * @access public
	 * @param  string   $property      The property name
	 * @param  mixed    $post          Post ID or post object {@see https://developer.wordpress.org/reference/functions/get_post/}
	 * @return mixed
	 */
	public function get_product_category( $property = '', $post = null ) {
		global $bws_bkng;

		$query    = bws_bkng_get_query();
		$cat_list = BWS_BKNG_List_Categories::get_instance();

		if ( ! empty( $query['bkng_category_id'] ) )
			return $cat_list->get_category( $query['bkng_category_id'], $property );

		$post = get_post( $post );

		if ( empty( $post->ID ) )
			return flase;

		if ( ! empty( $post->bkng_category_id ) )
			return $cat_list->get_category( $post->bkng_category_id, $property );

		/*$category = wp_get_post_terms( $post->ID, BWS_BKNG_CATEGORIES );

		if ( ! empty( $category[0] ) )
			$id = $cat_list->add_category( $category[0] );*/

		return empty( $id ) ? false : $cat_list->get_category( $id, $property );
	}

	/**
	 * Sorting the array by given field and sort order
	 * @see     http://php.net/manual/ru/function.array-multisort.php
	 * @param   array   $data
	 * @param   array   $sort_flags
	 * @return  array   $args
	 */
	public function list_sort( $data, $sort_flags ) {
		if( empty( $data ) ) {
			return array();
		}
		$args = array();
		$i    = 0;
		foreach( $sort_flags as $column => $sort_attr ) {
			$column_lists = array();
			foreach ( $data as $key => $row ) {
				$column_lists[ $column ][ $key ] =
						in_array( SORT_STRING, $sort_attr ) || in_array( SORT_REGULAR, $sort_attr )
					?
						strtolower( $row[ $column ] )
					:
						$row[ $column ];
			}
			$args[] = &$column_lists[ $column ];
			foreach( $sort_attr as $sort_flag ) {
				$tmp[ $i ] = $sort_flag;
				$args[]	= &$tmp[ $i ];
				$i++;
			}
		}
		$args[] = &$data;
		call_user_func_array( 'array_multisort', $args );
		return end( $args );
	}

	/**
	 * Applies the callback to the elements of the given array.
	 * @since  0.1
	 * @access public
	 * @param  string      $callback     The callback function to run for each element in the given array.
	 *                                   The callback must be the method of the BWS_BKNG class instance or it's helpers.
	 * @param  array       $arr          An array to run through the callback function.
	 * @param  maixed      $params       The callback-function parameters
	 * @return array
	 */
	public function array_map( $callback, $arr, $params ) {
		global $bws_bkng;
		$args = array();
		/**
		 * The items number in $arr and $args must be the same
		 * @see http://php.net/manual/function.array-map.php
		 */
		for ( $i = 0; $i < count( $arr ); $i ++ )
			$args[] = (array)$params;

		return array_map( array( $bws_bkng, $callback ), $arr, $args );
	}

	/**
	 * Applies the callback to the elements of the given multi-dimensional array.
	 * @since  0.1
	 * @access public
	 * @param  string      $callback     The callback function to run for each element in the given array.
	 * @param  array       $arr          An array to run through the callback function.
	 * @return array
	 */
	public function array_map_recursive( $callback, $arr ) {
		$out = array();

		foreach ( $arr as $key => $data )
			$out[ $key ] = is_array( $data ) ? $this->array_map_recursive( $callback, $data ) : $callback( $data );

		return $out;
	}

	/**
	 * Fetch the list of values from multidimensional array (list of arrays or list of object) by the specified key
	 * @see    self::array_map()
	 * @since  0.1
	 * @access public
	 * @param  array|object          $item
	 * @param  array                 $args
	 * @return mixed (array|object)
	 */
	public function array_column( $item, $args ) {

		$column_name = $args[0];

		if ( is_object( $item ) )
			return property_exists( $item, $column_name ) ? $item->$column_name : '';

		return empty( $item[ $column_name ] ) ? '' : $item[ $column_name ];
	}

	/**
	 * Fetch the list of registered products statuses.
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return array
	 */
	public function get_order_statuses() {
		return apply_filters(
			'bws_bkng_order_statuses',
			array(
				'on_hold'   => __( 'On hold', BWS_BKNG_TEXT_DOMAIN ),
				'processed' => __( 'Processed', BWS_BKNG_TEXT_DOMAIN ),
				'completed' => __( 'Completed', BWS_BKNG_TEXT_DOMAIN ),
				'canceled'  => __( 'Canceled', BWS_BKNG_TEXT_DOMAIN )
			)
		);
	}

	/**
	 * Fetch the types list of products attributes.
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return array
	 */
	public function get_meta_types() {
		return apply_filters(
			'bws_bkng_meta_types',
			array(
				'select'            => __( 'Drop Down List', BWS_BKNG_TEXT_DOMAIN ),
				'select_checkboxes' => __( 'List with Checkboxes', BWS_BKNG_TEXT_DOMAIN ),
				'select_radio'      => __( 'List with Radioboxes', BWS_BKNG_TEXT_DOMAIN ),
				'select_locations'  => __( 'List of Locations', BWS_BKNG_TEXT_DOMAIN ),
				'location'          => __( 'Single Location', BWS_BKNG_TEXT_DOMAIN ),
				'number'            => __( 'Number', BWS_BKNG_TEXT_DOMAIN ),
				'number_select'     => __( 'Select', BWS_BKNG_TEXT_DOMAIN ),
				'text'              => __( 'Text', BWS_BKNG_TEXT_DOMAIN ),
				'checkbox'          => __( 'Checkbox', BWS_BKNG_TEXT_DOMAIN )
			)
		);
	}

	/**
	 * Fetch the list of the plugin pages aliases.
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return array
	 */
	public function get_booking_pages() {
		return apply_filters(
			'bws_bkng_booking_pages',
			array(
				'products',
				'checkout',
				'thank_you',
				'agencies',
			)
		);
	}

	/**
	 * Fetch the list of week days names.
	 * @since  0.1
	 * @access public
	 * @param  boolean   $short   Wheter to return short or full name
	 * @return array
	 */
	public function get_week_days( $short = true ) {
		return
				$short
			?
				array(
					__( 'sun', BWS_BKNG_TEXT_DOMAIN ),
					__( 'mon', BWS_BKNG_TEXT_DOMAIN ),
					__( 'tue', BWS_BKNG_TEXT_DOMAIN ),
					__( 'wed', BWS_BKNG_TEXT_DOMAIN ),
					__( 'thu', BWS_BKNG_TEXT_DOMAIN ),
					__( 'fri', BWS_BKNG_TEXT_DOMAIN ),
					__( 'sat', BWS_BKNG_TEXT_DOMAIN )
				)
			:
				array(
					__( 'Sunday', BWS_BKNG_TEXT_DOMAIN ),
					__( 'Monday', BWS_BKNG_TEXT_DOMAIN ),
					__( 'Tuesday', BWS_BKNG_TEXT_DOMAIN ),
					__( 'Wednesday', BWS_BKNG_TEXT_DOMAIN ),
					__( 'Thursday', BWS_BKNG_TEXT_DOMAIN ),
					__( 'Friday', BWS_BKNG_TEXT_DOMAIN ),
					__( 'Saturday', BWS_BKNG_TEXT_DOMAIN )
				);
	}

	/**
	 * Fetch the list of registered products rent intervals.
	 * @since  0.1
	 * @access public
	 * @param  string   $key        The rent interval slug.
	 * @param  string   $return     Wheter to return all data (if the value is "all"),
	 *                              the interval(s) gap value(s) (if the value is "number") or
	 *                              the interval(s) key(s)
	 * @return mixed
	 */
	public function get_rent_interval( $key = '', $return = 'all' ) {
		global $bws_bkng;
		$intervals = array(
			'none'  => array( __( 'none',  BWS_BKNG_TEXT_DOMAIN ), 0                ),
			'hour'  => array( __( 'hour',  BWS_BKNG_TEXT_DOMAIN ), HOUR_IN_SECONDS  ),
			'day'   => array( __( 'day',   BWS_BKNG_TEXT_DOMAIN ), DAY_IN_SECONDS   ),
			'night' => array( __( 'night',   BWS_BKNG_TEXT_DOMAIN ), DAY_IN_SECONDS   ),
			'week'  => array( __( 'week',  BWS_BKNG_TEXT_DOMAIN ), WEEK_IN_SECONDS  ),
			'month' => array( __( 'month', BWS_BKNG_TEXT_DOMAIN ), MONTH_IN_SECONDS ),
			'year'  => array( __( 'year',  BWS_BKNG_TEXT_DOMAIN ), YEAR_IN_SECONDS  )
		);
		switch( $key ) {
			case '':
				return 'keys' == $return ? array_keys( $intervals ) : $intervals;
			case 'none':
			case 'hour':
			case 'day':
			case 'night':
			case 'week':
			case 'month':
			case 'year':
				if ( 'number' == $return )
					return $intervals[ $key ][1];
				elseif ( 'label' == $return )
					return $intervals[ $key ][0];
				else
					return $intervals[ $key ];
			default:
				return false;
		}
	}

	/**
	 * Fetch the list of order parameters to be used in the products list toolbar in the frontend.
	 * @since  0.1
	 * @access public
	 * @param  int      $cat_id     The category ID.
	 * @return string
	 */
	public function get_order_by_fields( $only_keys = false ) {
		$args = apply_filters(
			'bws_bkng_order_fields',
			array(
				'price' => __( 'Price', BWS_BKNG_TEXT_DOMAIN ),
				'date'  => __( 'Date', BWS_BKNG_TEXT_DOMAIN ),
				'title' => __( 'Title', BWS_BKNG_TEXT_DOMAIN )
			)
		);
		return $only_keys ? array_keys( $args ) : $args;
	}

	/**
	 * Fetch the rent interval slug of the given products category.
	 * @since  0.1
	 * @access public
	 * @param  int      $cat_id     The category ID.
	 * @return string
	 */
	public function get_category_rent_interval( $cat_id ) {
		if ( ! is_numeric( $cat_id ) ) {

			$category = get_term_by( 'slug', $cat_id, BWS_BKNG_CATEGORIES );

			if ( ! $category )
				return 'day';

			$cat_id = $category->term_id;
		}

		$interval = get_term_meta( absint( $cat_id ), 'bkng_rental_interval', true );
		return empty( $interval ) || ! in_array( $interval, $this->get_rent_interval( '', 'keys' ) ) ? 'day' : $interval;
	}

	/**
	 * Fetch the rent interval slug of the given product.
	 * @since  0.1
	 * @access public
	 * @param  int      $post_id     The product ID.
	 * @return string
	 */
	public function get_product_rent_interval( $post_id ) {
		return 'day';
	}


	/**
	 * Fetch the list of category attributes
	 * @see    add_attributes_tab()
	 * @since  0.1
	 * @access public
	 * @param  string                $category_id      The category ID
	 * @return array|boolean(false)                    The list of attributes data, false  otherwise
	 */
	public function get_category_attributes( $category_id = '' ) {
		global $bws_bkng, $wpdb;

		$category_id = absint( $category_id );

		if ( empty( $category_id ) )
			return false;

		/**
		 * Get associated attributes
		 */
		$associated_atts = $wpdb->get_col(
		    $wpdb->prepare(
                "SELECT `attribute_slug`
                FROM `" . BWS_BKNG_DB_PREFIX . "cat_att_dependencies`
                WHERE `category_id`=%d;",
                $category_id
            )
		);

		if ( empty( $associated_atts ) )
			return false;

		return array_intersect_key(
			$bws_bkng->get_option( 'attributes' ),
			array_flip( $associated_atts )
		);
	}

	/**
	 * Fetch the list of the Booking core capabilities
	 * Most of specified capabilities are generated during the Bokking post type registration except "recieve_bkng_notifications"
	 * which is additionaly added in order to set the users that can recieve notifications as agents.
	 * @see https://codex.wordpress.org/Function_Reference/register_post_type#capability_type
	 * @since  0.1
	 * @access public
	 * @return array
	 */
	public function get_caps_list() {
		return array(
			'edit_bws_bkng_product',
			'read_bws_bkng_product',
			'delete_bws_bkng_product',
			'edit_bws_bkng_products',
			'edit_others_bws_bkng_products',
			'publish_bws_bkng_products',
			'read_private_bws_bkng_products',
			'delete_bws_bkng_products',
			'delete_private_bws_bkng_products',
			'delete_published_bws_bkng_products',
			'delete_others_bws_bkng_products',
			'edit_private_bws_bkng_products',
			'edit_published_bws_bkng_products',
			'send_bws_bkng_notifications',
			'recieve_bws_bkng_notifications'
		);
	}

	/**
	 * Fetc the agencies registered meta-fields data
	 * @since  0.1
	 * @access public
	 * @param  boolean     $return_keys    Whether to reeturn meta-fields slugs only
	 * @return array
	 */
	public function get_agencies_meta_fields( $return_keys = false ) {
		$meta_fields = apply_filters(
			'bws_bkng_agencies_meta_fields',
			array(
				'location'       => __( 'Location', BWS_BKNG_TEXT_DOMAIN ),
				'phone'          => __( 'Phone', BWS_BKNG_TEXT_DOMAIN ),
				'working_hours'  => __( 'Working Hours', BWS_BKNG_TEXT_DOMAIN ),
				'featured_image' => __( 'Featured Image', BWS_BKNG_TEXT_DOMAIN ),
				'image_gallery'  => __( 'Gallery', BWS_BKNG_TEXT_DOMAIN )
			)
		);
		return $return_keys ? array_keys( $meta_fields ) : $meta_fields;
	}

	/**
	 * Fetch the list of registered users which have necessary capabilities
	 * @since  0.1
	 * @access public
	 * @param array|string   $caps              The capability slug that users need to have.
	 * @param string|array   $fields            Which fields are need to be returned.
	 * @param boolean        $return_array      Whether to return data as array or object.
	 * @return array|object
	 */
	function get_agents( $caps = '', $fields = '', $return_array = true ) {
		$args = array( 'role__in' => array( 'administrator', 'bws_bkng_agent' ) );
		$agents_caps = $this->get_caps_list();

		if ( ! empty( $caps ) ) {
			foreach( (array)$caps as $cap ) {
				if ( in_array( $cap, $agents_caps ) )
					array_push( $args['role__in'], $cap );
			}
		}

		$query = new WP_User_Query( $args );
		$agents = $query->get_results();

		if ( empty( $agents ) )
			return false;

		if ( empty( $fields ) )
			return $agents;

		$data = array();
		$get_several_columns = is_array( $fields );

		foreach ( $agents as $agent ) {
			if ( $get_several_columns ) {
				$temp = array();
				foreach ( $fields as $field ) {
					if ( property_exists( $agent, $field ) )
						$temp[$field] = $agent->$field;
					elseif ( property_exists( $agent->data, $field ) )
						$temp[$field] = $agent->data->$field;
				}
				if ( ! empty( $temp ) )
					$data[] = $return_array ? $temp : (object)$temp;
			} elseif( property_exists( $agent, $fields ) ) {
				$temp   = array( $fields => $agent->$fields );
				$data[] = $return_array ? $temp : (object)$temp;
			} elseif ( property_exists( $agent->data, $field ) ) {
				$temp   = array( $fields => $agent->data->$fields );
				$data[] = $data[] = $return_array ? $temp : (object)$temp;
			}
		}

		return empty( $data ) ? false : $data;
	}

	/**
	 * Fetch the list of registered endpoints for the plugin pages
	 * The functionality of user account page is not over yet.
	 * @todo  Uncomment and make necessary changes during the developing the
	 * appropriate functionality.
	 * @since  0.1
	 * @access public
	 * @param   string   $type   The page alias
	 */
	// public function get_endpoints( $key = 'user_accounts' ) {
	// 	$endpoints = apply_filters(
	// 		'bws_bkng_endpoints',
	// 		array(
	// 			'user_accounts' => array(
	// 				'history'       => array( __( 'History', BWS_BKNG_TEXT_DOMAIN ), __( 'History of visits, views, etc.', BWS_BKNG_TEXT_DOMAIN ) ),
	// 				'orders'        => array( __( 'Orders', BWS_BKNG_TEXT_DOMAIN ), __( 'Page with a list of previous and current user orders', BWS_BKNG_TEXT_DOMAIN ) ),
	// 				'view_order'    => array( __( 'View Order', BWS_BKNG_TEXT_DOMAIN ), __( 'The order data view page', BWS_BKNG_TEXT_DOMAIN ) ),
	// 				'settings'      => array( __( 'Settings', BWS_BKNG_TEXT_DOMAIN ), __( 'Profile settings page', BWS_BKNG_TEXT_DOMAIN ) ),
	// 				'lost_password' => array( __( 'Lost Password', BWS_BKNG_TEXT_DOMAIN ), __( 'Change password page', BWS_BKNG_TEXT_DOMAIN ) ),
	// 				'logout'        => array( __( 'User Logout', BWS_BKNG_TEXT_DOMAIN ), __( 'User logout page', BWS_BKNG_TEXT_DOMAIN ) )
	// 			),
	// 			'checkout' => array(
	// 			),
	// 		)
	// 	);
	// 	return array_key_exists( $key, $endpoints ) ? $endpoints[ $key ] : null;
	// }

}
