<?php
/**
 * @uses to fetch the list of the product meta data
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Post_Data' ) )
	return;

class BWS_BKNG_Post_Data {

	public $attributes;	

	private $post;

	/**
	 * Contains an instance of the current class
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    object
	 */
	private static $instance = NULL;

	/**
	 * Class constructor
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   void
	 */
	public function __construct( $post = null ) {
		global $bws_bkng, $wpdb;

		$this->post = get_post( $post );

		$product_attributes = $wpdb->get_results( 'SELECT * FROM `' . BWS_BKNG_DB_PREFIX . $this->post->post_type . '_field_ids`', ARRAY_A );

		if ( ! empty( $product_attributes ) ) {
			$product_attributes_data = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM `' . BWS_BKNG_DB_PREFIX . $this->post->post_type . '_field_post_data` WHERE `post_id` = %d', $this->post->ID ), ARRAY_A );
			foreach ( $product_attributes as $key => $attribute ) {
				$this->attributes[ $attribute['field_slug'] ] = $attribute;
				$product_attribute_value = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM `' . BWS_BKNG_DB_PREFIX . $this->post->post_type . '_field_values` WHERE `field_id` = %d', $attribute['field_id'] ), ARRAY_A );
				if ( ! empty( $product_attribute_value ) ) {
					foreach( $product_attribute_value as $attribute_value ) {
						$this->attributes[ $attribute['field_slug'] ]['field_values'][ $attribute_value['value_id'] ] = $attribute_value;
					}					
				}
				foreach ( $product_attributes_data as $product_data ) {
					if ( $attribute['field_id'] == $product_data['field_id'] ) {
						$this->attributes[ $attribute['field_slug'] ]['field_data'][] = $product_data;
					}
				}
			}
		}
	}

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
	 * Get attribute data
	 * @since    0.1
	 * @access   public
	 * @static
	 * @param    string		Attribute slug without plugin prefix
	 * @return   array|bool    A value of the attribute
	 */
	public function get_attribute( $slug ) {
		global $bws_bkng;

		if ( ! empty( $this->attributes ) && ! empty( $slug ) ) {
			if ( ! isset( $this->attributes[ $slug ] ) ) {
				$slug = $bws_bkng->plugin_prefix . '_' . $slug;
			}
			if ( isset( $this->attributes[ $slug ]['field_data'] ) ) {
				if ( is_array( $this->attributes[ $slug ]['field_data'] ) && 1 < count( $this->attributes[ $slug ]['field_data'] ) ) {
					$value_array = array();
					foreach( $this->attributes[ $slug ]['field_data'] as $value ) {
						if ( ! empty( $this->attributes[ $slug ]['field_values'] ) && isset( $this->attributes[ $slug ]['field_values'][ $value['post_value'] ] ) ) {
							$value_array[] = $this->attributes[ $slug ]['field_values'][ $value['post_value'] ];
						} else {
							$value_array[] = $value['post_value'];
						}
					}
					return $value_array;
				} else {
					if ( ! empty( $this->attributes[ $slug ]['field_values'] ) && isset( $this->attributes[ $slug ]['field_values'][ $this->attributes[ $slug ]['field_data'][0]['post_value'] ] ) ) {
						return $this->attributes[ $slug ]['field_values'][ $this->attributes[ $slug ]['field_data']['post_value'] ]['value_name'];
					} else {
						return $this->attributes[ $slug ]['field_data'][0]['post_value'];
					}
				}
			}
		}

		return false;
	}

	/**
	 * Get list attribute data (user custom attribute)
	 * @since    0.1
	 * @access   public
	 * @static
	 * @param    string		Attribute slug
	 * @return   bool|array      A value of the attribute
	 */
	public function get_custom_attribute_list() {
		global $bws_bkng;

		if ( empty( $this->attributes ) ) {
			return false;
		}

		$attributes_list = array();
		foreach( $this->attributes as $slug => $value ) {
			if ( false !== strpos( $slug, $bws_bkng->plugin_prefix ) ) {
				continue;
			}
			if ( isset( $value['field_data'] ) ) {
				if ( is_array( $value['field_data'] ) ) { 
					$value_array = array();
					foreach( $value['field_data'] as $field_data_value ) {
						if ( ! empty( $value['field_values'] ) && isset( $value['field_values'][ $field_data_value['post_value'] ] ) ) {
							$value_array[] = $value['field_values'][ $field_data_value['post_value'] ]['value_name'];
						} else {
							$value_array[] = $field_data_value['post_value'];
						}
					}
				} else {
					$value_array = $value['field_data'][0]['post_value'];
				}
			}
			if ( empty( $value_array ) ) {
				continue;
			}

			$attributes_list[ $slug ] = array( 
				'label' => $value['field_name'],
				'value' => $value_array,
			);
		}

		return $attributes_list;
	}

	/**
	 * Update or insert attribute value
	 * @since    0.1
	 * @access   public
	 * @static
	 * @param    string		Attribute slug without plugin prefix
	 * @return   bool    	true if value was updated|inserted, false otherwise
	 */
	public function update_attribute( $slug, $value ) {
		global $bws_bkng, $wpdb;

		if ( ! isset( $this->attributes[ $slug ] ) ) {
			$slug = $bws_bkng->plugin_prefix . '_' . $slug;

			if ( ! isset( $this->attributes[ $slug ] ) ) {
				return false;
			}
		}

		if ( ! $this->get_attribute( $slug ) ) {
			$result = $wpdb->insert(
				BWS_BKNG_DB_PREFIX . $this->post->post_type . '_field_post_data',
				array(
					'field_id'		=> $this->attributes[ $slug ]['field_id'],
					'post_id'		=> $this->post->ID,
					'post_value'	=> maybe_serialize( $value ),
				),
				array( 
					'%d',
					'%d',
					'%s',
				)
			);
		} else {
			$result = $wpdb->update(
				BWS_BKNG_DB_PREFIX . $this->post->post_type . '_field_post_data',
				array(
					'post_value'	=> maybe_serialize( $value ),
				),
				array(
					'field_id'		=> $this->attributes[ $slug ]['field_id'],
					'post_id'		=> $this->post->ID,
				),
				array(
					'%s',
				),
				array( 
					'%d',
					'%d',
				)
			);
		}

		return !! $result;
	}

	/**
	 * Some magic to avoid the creation of several instances of this class
	 * @since  0.1
	 */
	private function __clone()  {}
	private function __sleep()  {}
	private function __wakeup() {}

}
