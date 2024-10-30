<?php
/**
 * Monitors the user search request and generate data for the further the products list forming
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Query_Parser' ) )
	return;

class BWS_BKNG_Query_Parser {

	/**
	 * Contains an instance of the current class
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    object
	 */
	private static $instance;

	/**
	 * Conatains the list of the queried parameters
	 * @since  0.1
	 * @access private
	 * @var    array
	 */
	private $query_args = array();

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
	 * Fetch the queried parameters
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   array
	 */
	public function get_query_args() {
		return $this->query_args;
	}

	/**
	 * Class constructor
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   void
	 */
	private function __construct() {
		global $bws_bkng, $wp_query, $wpdb, $bws_post_type, $bws_search_form_post_type;
		$search_query = $tax_query = $meta_query = $statuses = array();

		/*
		 * Add primary data
		 */
		$this->query_args['post_type']   = $bws_bkng->get_post_types();
		$this->query_args['post_status'] = is_user_logged_in() ? array( 'publish', 'private' ) : 'publish';

		$this->query_args = array_merge( $this->query_args, $wp_query->query );

		/*
		 * Remove excess data
		 */
		$remove_keys = array_intersect(
			array_keys( $this->query_args ),
			apply_filters( 'bws_bkng_remove_query_vars', array( 'page_id', 'page', 'pagename' ) )
		);
		if ( ! empty( $remove_keys ) ) {
			foreach ( $remove_keys as $key )
				unset( $this->query_args[ $key ] );
		}

		/*
		 * Set the search query content
		 */
		$raw_search_data = $this->get_search_query();
		if ( ! empty( $raw_search_data ) ) {

			foreach( $raw_search_data as $key => $value ) {
				switch ( $key ) {
					/**
					 * Rent interval fieds ( 'form' and 'till' ) are parts of the search query due to:
					 * 1. In order to make an ability to search products by theirs value ( for future development )
					 * 2. In order to not specify additional rewrite rules with these fields { @see BWS_BKNG_Front::add_rewrite_rules() }
					 */
					case 'from':
					case 'till':
						$timestamp = absint( $value );
						$$key = $timestamp && $timestamp > strtotime( "+1 hour" ) ? $timestamp : false;
						if ( $$key )
							$value = $timestamp;
						break;
				}
				$search_query[ $key ] = $value;
			}
		}
		if ( ! empty( $_GET['bws_bkng_action'] ) && 'search' == $_GET['bws_bkng_action'] ) {
			foreach( $_GET as $key => $value ) {
				$search_query[ $key ] = $value;
				if( 'bws_bkng_post_type' == $key ) {
					$this->query_args['post_type'] = $value;
				}
			}
		} else {
			$this->query_args['post_type'] = array_keys( $bws_post_type );
		}

		/**
		 * Save the search query in dedicated field
		 * @uses in the plugin search fliter
		 * @see  class BWS_BKNG_Search_Filter definition
		 */
		if ( ! empty( $search_query ) )
			$this->query_args['search'] = $search_query;

		/*
		 * Fetch the query set by taxonomies
		 */
		if ( ! empty( $tax_query ) ) {
			$this->query_args['tax_query'] = array_merge(
				array( 'relation' => $this->get_relation( 'tax_query' ) ),
				$tax_query
			);
		}

		/*
		 * Add Sort fields
		 */
		$orderby_fields = $bws_bkng->get_order_by_fields( true );
		if ( empty( $orderby ) || ! in_array( $orderby, $orderby_fields ) ) {
			$orderby =
					! empty( $_GET['orderby'] ) && in_array( $_GET['orderby'], $orderby_fields )
				?
					sanitize_text_field( stripslashes( $_GET['orderby'] ) )
				:
					$bws_bkng->get_post_type_option( $this->query_args['post_type'], 'sort_products_by' );

			if ( empty( $orderby ) )
				$orderby = 'date';
		}

		switch ( $orderby ) {
			case 'date':
			case 'title':
				$this->query_args['orderby'] = $orderby;
				break;
			default:
				break;
		}

		$order_fields = array( 'asc', 'desc' );
		$order = isset( $search_query['order'] ) ? $search_query['order'] : '';
		if ( empty( $order ) || ! in_array( strtolower( $order ), $order_fields ) ) {
			$order =
					! empty( $_GET['order'] ) && in_array( strtolower( $_GET['order'] ), $order_fields )
				?
					sanitize_text_field( stripslashes( $_GET['order'] ) )
				:
					$bws_bkng->get_post_type_option( $this->query_args['post_type'], 'sort_products' );

			if ( empty( $orderby ) )
				$orderby = 'asc';
		}

		$this->query_args['order'] = strtolower( $order );

		$views = array( 'list', 'grid' );
		if ( empty( $view ) || ! in_array( $view, $views ) ) {
			$view =
					! empty( $_GET['view'] ) && in_array( strtolower( $_GET['view'] ), $views )
				?
					sanitize_text_field( stripslashes( $_GET['view'] ) )
				:
					'grid';
		}

		$this->query_args['view'] = $view;

		/**
		 * Add the number of products per page.
		 * The 'show' parameter is used instead of 'per_page' from WP core due to the fact
		 * that in the function paginate_links() there is a replacement "per_page/{products_on_page_number}" with
		 * per_/page/{current_page_number} if this endpoint specified in the end of the link and it breaks the plugin rewrite rules
		 */
		$show = empty( $show ) ? 0 : absint( $show );
		if ( empty( $show ) )
			$show = empty( $_GET['show'] ) ? get_option( 'posts_per_page' ) : absint( $_GET['show'] );

		$this->query_args['posts_per_page'] = $show;

		$this->query_args = apply_filters( 'bws_bkng_query_args', $this->query_args );
	}

	/**
	 * Fetch the list of search query parameters
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   array|bool  $return
	 */
	private function get_search_query() {
		global $bws_bkng, $wp_query;

		/**
		 * If JS is disblaed or broken
		 * @todo This functionality is not ovaer yet and it is need to be complete in one of the next core updates
		 */
		if ( isset( $_POST['bws_bkng_search'] ) )
			return array_map( 'sanitize_text_field', $_POST['bws_bkng_search']);

		if ( empty( $wp_query->query['search'] ) ) {
			return false;
		}

		$query_parts = array_filter( explode( '/', $wp_query->query['search'] ) );

		if ( 2 > count( $query_parts ) )
			return false;

		$return = array();

		for ( $i = 0; $i < count( $query_parts ); $i += 2 ) {
			$key = sanitize_title( $query_parts[ $i ] );
			$value = isset( $query_parts[ $i + 1 ] ) ? trim( $query_parts[ $i + 1 ] ) : false;

			if ( empty( $key ) || empty( $value ) )
				continue;

			$return[ $key ] = $value;
		}

		return $return;

	}

	/**
	 * Fetch the parameters of numeric queries
	 * @since    0.1
	 * @access   private
	 * @param    string                $key       The query parameter's key
	 * @param    string                $value     The query parameter's value
	 * @return   array/false                      The wp_query parameter - if queried parameter is OK, false otherwise
	 */
	private function get_numeric_meta_query( $key, $value ) {
		global $bws_bkng;

		$value = $bws_bkng->sanitize_number_range( $value );

		if ( empty( $value ) )
			return false;

		$query_args = array(
			'key'     => 'bkng_' . $key,
			'value'   => $value
		);

		if ( is_array( $value ) ) {
			$query_args['compare'] = 'BETWEEN';
			$query_args['type']    = 'numeric';
		} else {
			$query_args['compare'] = '=';
		}

		return $query_args;
	}

	/**
	 * Fetch the relations between equal sql parameters in WHERE clause
	 * @since    0.1
	 * @access   private
	 * @param    string   $type     The searched parameter item type ( 'meta_query' or 'tax_query' )
	 * @return   string             'AND' or 'OR'
	 */
	private function get_relation( $type ) {
		$relation = apply_filters( "bws_bkng_{$type}_relation", 'AND' );
		return in_array( $relation, array( 'AND', 'OR' ) ) ? $relation : 'AND';
	}

	/**
	 * Some magic to avoid the creation of several instances of this class
	 * @since  0.1
	 */
	private function __clone()  {}
	private function __sleep()  {}
	private function __wakeup() {}
}
