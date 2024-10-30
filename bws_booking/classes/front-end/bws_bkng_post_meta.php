<?php
/**
 * @uses to fetch the list of the product meta data
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Post_Meta' ) )
	return;

class BWS_BKNG_Post_Meta {

	/**
	 * Contains an instance of the current class
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    object
	 */
	private static $instance;

	/**
	 * Contains the ID of current category
	 * @since  0.1
	 * @access private
	 * @var    int
	 */
	private $cat_id;

	/**
	 * The list of post meta data
	 * @since  0.1
	 * @access private
	 * @var    array
	 */
	private $post_meta_fields = array();

	/**
	 * The list of post terms data
	 * @since  0.1
	 * @access private
	 * @var    array
	 */
	private $post_term_fields = array();

	/**
	 * WP_POST object, the current product's data
	 * @since  0.1
	 * @access private
	 * @var    object
	 */
	private $post;

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
	 * Fetch the list of product data
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   array
	 */
	public function get_meta() {

		$prepared = array(
			'primary'   => array(),
			'secondary' => array()
		);

		$this->post = get_post();

		$raw = $this->get_post_meta();

		if ( ! empty( $raw ) ) {
			foreach ( $raw as $data ) {

				/**
				 * Check whether the meta-field is part of the single-location attribute.
				 * Such thing is neede due to the fact that the single-location attribute data are kept in 3 meta-fields 'address', 'longitude' or 'latitude'
				 * for an ability to make search by them
				 */
				$is_location = $this->is_location_field( $data->meta_key );
				if ( $is_location && ! empty( $is_location[1] ) && array_key_exists( "bkng_{$is_location[1]}", $this->post_meta_fields ) ) {

					$loc_meta_name = "bkng_{$is_location[1]}";
					$loc_part_name = $is_location[2];
					$loc_prepared_key = empty( $this->post_meta_fields[ $loc_meta_name ]['primary'] ) ? 'secondary' : 'primary';

					if ( empty( $prepared[ $loc_prepared_key ][ $loc_meta_name ] ) ) {
						$prepared[ $loc_prepared_key ][ $loc_meta_name ] = array(
							'label' => $this->post_meta_fields[ $loc_meta_name ]['label'],
							'value' => ''
						);
					}

					$prepared[ $loc_prepared_key ][ $loc_meta_name ][ $loc_part_name ] = $data->meta_value;

					continue;

				}

				if ( empty( $data->meta_value ) || ! array_key_exists( $data->meta_key, $this->post_meta_fields ) )
					continue;

				/*
				 * Add necessary data to the result array
				 */
				$meta_data = $this->post_meta_fields[ $data->meta_key ];

				$prepared_key = empty( $meta_data['primary'] ) ? 'secondary' : 'primary';

				if ( empty( $prepared[ $prepared_key ][ $data->meta_key ] ) )
					$prepared[ $prepared_key ][ $data->meta_key ] = array();

				$prepared[ $prepared_key ][ $data->meta_key ] = array(
					'label' => $meta_data['label'],
					'value' => $data->meta_value . ( empty( $meta_data['meta_options']['number_measure'] ) ? '' : " {$meta_data['meta_options']['number_measure']}" )
				);
			}
		}

		$raw = $this->get_post_taxonomies();

		if ( ! empty( $raw ) ) {
			foreach( $raw as $key => $data ) {

				if ( ! array_key_exists( $key, $this->post_term_fields ) )
					continue;

				$meta_data = $this->post_term_fields[ $key ];
				$prepared_key = empty( $meta_data['primary'] ) ? 'secondary' : 'primary';
				$prepared[ $prepared_key ][ $key ] = array( 'label' => $meta_data['label'] );

				if ( 'select_locations' == $meta_data['meta_type'] )
					$prepared[ $prepared_key ][ $key ] = array_merge( $prepared[ $prepared_key ][ $key ], $data );
				else
					$prepared[ $prepared_key ][ $key ]['value'] = implode( ', ', $data );
			}
		}

		return $prepared;
	}

	/**
	 * Fetch the raw list of product's meta data
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   array|false
	 */
	private function get_post_meta() {
		global $wpdb;

		if ( empty( $this->cat_id ) || empty( $this->post_meta_fields ) )
			return false;

		$prefix    = BWS_BKNG_DB_PREFIX;
		$keys      = implode( "','", $this->get_meta_keys() );
		$post_meta = $wpdb->get_results(
		    $wpdb->prepare(
                "SELECT `meta_value`, `meta_key`
                FROM `{$wpdb->postmeta}`
                WHERE `post_id`=%d AND `meta_key` IN (%s);",
                $this->post->ID,
                $keys
            )
		);

		return empty( $post_meta ) || $wpdb->last_error ? false : $post_meta;
	}

	/**
	 * Fetch the raw list of product's terms
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   array|false
	 */
	private function get_post_taxonomies() {

		$terms = array();

		foreach( $this->post_term_fields as $slug => $data ) {
			$is_location   = 'select_locations' == $data['meta_type'];
			$tax           = str_replace( 'bkng_', '',  $slug );
			$tags          = wp_get_post_terms( $this->post->ID, $slug );
			$location_meta = array( 'address', 'latitude', 'longitude' );

			if ( empty( $tags ) || is_wp_error( $tags ) )
				continue;

			if ( ! isset( $terms[ $slug ] ) )
				$terms[ $slug ] = array();

			foreach( $tags as $tag ) {

				if ( $is_location ) {

					foreach( $location_meta as $key )
						$terms[ $slug ][ $key ] = get_term_meta( $tag->term_id, "bkng_term_{$key}", true );

					continue;

				}

				$terms[ $slug ][] = $tag->name;

			}
		}

		return empty( $terms ) ? false : $terms;
	}

	/**
	 * Fetch the list of the product's meta-fields
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   array      The list of the product meta-fields' names
	 */
	private function get_meta_keys() {
		$keys = array();

		foreach ( $this->post_meta_fields as $key => $data ) {
			if ( 'location' == $data['meta_type'] ) {
				$keys[] = "{$key}_address";
				$keys[] = "{$key}_latitude";
				$keys[] = "{$key}_longitude";
			}

			$keys[] = $key;
		}
		return $keys;
	}

	/**
	 * Checks whether the current $meta field is a part of product location data
	 * @since    0.1
	 * @access   private
	 * @param    string     $meta_name      The meta-field name
	 * @param    string     $field_name     The meta-field type ('address', 'longitude', 'latitude').
	 * @return   array
	 */
	private function is_location_field( $meta_name, $field_name = '' ) {

		$field_name = in_array( $field_name, array( 'address', 'longitude', 'latitude' ) ) ? $field_name : '';

		preg_match( "/^bkng_([\S]+)_(address|longitude|latitude)$/i", $meta_name, $matches );

		if ( empty( $matches ) || ! array_key_exists( "bkng_{$matches[1]}", $this->post_meta_fields ) )
			return false;

		return $matches;
	}


	/**
	 * Class constructor
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   void
	 */
	private function __construct() {
		global $bws_bkng;

		$cat_id = $bws_bkng->get_product_category( 'term_id' );

		if ( empty( $cat_id ) )
			return;

		$this->cat_id = $cat_id;

		$cat_atts = $bws_bkng->get_category_attributes( $this->cat_id );

		if ( empty( $cat_atts ) )
			return;

		/**
		 * Split attributes in two groups. The first one is taxonomies (list-type attributes), the last one is post meta-fields.
		 * It is necessary because for the taxonomies and pos meta-fields there are different data processing before the output.
		 */
		foreach ( $cat_atts as $key => $data ) {

			$var = $bws_bkng->is_taxonomy( $data['meta_type'] ) ? 'post_term_fields' : 'post_meta_fields';

			$this->{$var}[$key] = $data;
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