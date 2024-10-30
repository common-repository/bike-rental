<?php
/**
 * Handles the order.
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

/**
 * Default Order content structure
 * @var array
 * array(
 * 	products => array(
 * 		{product_id} => array(
 * 			rent_interval => @var array('from'=>{unix_timestamp},'till'={unix_timestamp}, 'step'={int})|false  Array - for for-rent-products, false otherwise
 * 			quantity      => @var int|false    int - it is extra for for-sale-products, false otherwise,
 * 			linked_to     => @var int|false    int - main products id, false otherwise (if it is the main product),
 * 			price         => @var int          Start price for the moment when product was added to the cart,
 * 			subtotal      => @var int
 * 			total         => @var int
 * 		),
 * 		{product_id} => array( ... ),
 * 		{product_id} => array( ... ),
 * 		{product_id} => array( ... ),
 * 		...
 * 	),
 * 	extras_total => @var int
 * 	subtotal     => @var int
 * 	total        => @var int
 * )
 */

if ( class_exists( 'BWS_BKNG_Order' ) )
	return;


class BWS_BKNG_Order extends BWS_BKNG_Order_Handler {

	/**
	 * Contains an instance of the current class
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    object
	 */
	private static $instance;

	/**
	 * Contains the date interval that was entered by user in the search products form
	 * @since  0.1
	 * @access private
	 * @var    array     format: array('from'=>{unix_timestamp},'till'={unix_timestamp} )
	 */
	private $rent_interval;

	/**
	 * Conatains the data that were entered by user to the checkout form
	 * @since  0.1
	 * @access private
	 * @var    array     format: array(
	 *                      'user_id'               => {int},
	 *                      'user_firstname'        => {string},
	 *                      'user_lastname'         => {string},
	 *                      'user_phone'            => {string},
	 *                      'user_email'            => {string},
	 *                      'user_confirm_email'    => {string},
	 *                      'user_message'          => {string},
	 *                      'user_agree_with_terms' => {boolean}
	 *                   }
	 */
	private $billing_data;

	/**
	 * The currently handled order ID
	 * @since  0.1
	 * @access private
	 * @var    int
	 */
	private $order_id;

	private $post_type;

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
	 * Fetch the ordered products' list.
	 * For previously saved orders it also will return billing data, order status, date of creation etc.
	 * @since    0.1
	 * @access   public
	 * @param    int       $order_id         The order ID
	 * @return   array|bool     $order_data
	 */
	public function get( $order_id = '' ) {
		global $wpdb, $bws_bkng;

		$order_id = absint( $order_id );
		$prefix   = BWS_BKNG_DB_PREFIX .  $this->post_type . '_';

		if ( ! $order_id )
			return $this->content;

		$order_data = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . $prefix . 'orders` WHERE `id`= %d LIMIT 1;', $order_id ), ARRAY_A );

		if ( ! $order_data )
			return false;

		$order_meta = $wpdb->get_results( $wpdb->prepare( 'SELECT `meta_key`, `meta_value` FROM `' . $prefix . 'orders_meta` WHERE `order_id`= %d;', $order_id ), ARRAY_A );

		$order_poducts = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM `' . $prefix . 'ordered_products` WHERE `order_id`= %d;', $order_id ), ARRAY_A );

		foreach( $order_meta as $meta ) {
			switch ( $meta['meta_key'] ) {
				case 'currency_code':
				case 'currency_position':
				case 'user_age':
					$order_data[ $meta['meta_key'] ] = maybe_unserialize( $meta['meta_value'] );
					break;
				default:
					$order_data['meta'][ $meta['meta_key'] ] = maybe_unserialize( $meta['meta_value'] );
			}
		}

		$order_data['products']     = array();
		$order_data['extras_total'] = 0;

		foreach( $order_poducts as $product ) {

			$post    = get_post( $product['product_id'] );
			$is_post = ! empty( $post );

			if ( $is_post ) {
				$is_variation = $bws_bkng->allow_variations && ! empty( $post->post_type ) && BWS_BKNG_VARIATION == $post->post_type && ! empty( $post->post_parent );
				$sku          = get_post_meta( $post->ID, 'bkng_sku', true );
				if ( empty( $sku ) )
					$sku = "#{$post->ID}";
				$title     = $is_variation ? get_the_title( $post->post_parent ) : get_the_title( $post );
				$edit_link = $is_variation ? add_query_arg( 'bkng_variation', $product['product_id'], get_edit_post_link( $post->post_parent ) ) : get_edit_post_link( $post->ID );
			} else {
				$is_variation = false;
				$sku          = '#' . $product['product_id'];
				$title        = __( 'product removed', BWS_BKNG_TEXT_DOMAIN );
				$edit_link    = '';
				$order_data['products'][ $product['product_id'] ]['removed'] = true;
			}

			if ( $product['linked_to'] )
				$order_data['extras_total'] += $product['total'];


			$order_data['products'][ $product['product_id'] ] = array(
				'title'         => $title,
				'rent_interval' => array( 'from'=> strtotime( $product['rent_interval_from'] ), 'till'=> strtotime( $product['rent_interval_till'] ), 'step' => absint( $product['rent_interval_step'] ) ),
				'sku'           => $sku,
				'quantity'      => $product['quantity'],
				'linked_to'     => $product['linked_to'],
				'price'         => $product['price'],
				'subtotal'      => $product['subtotal'],
				'total'         => $product['total']
			);

			if ( $bws_bkng->is_admin() )
				$order_data['products'][ $product['product_id'] ]['edit_post_link'] = $edit_link;
		}

		$this->content = $order_data;

		return $order_data;
	}

	/**
	 * Merges the order changes
	 * @since    0.1
	 * @access   private
	 * @param    array     $order_data         The order data
	 * @return   array                         format: {@see:billing_data}
	 */
	private function check_products_changes() {
		global $bws_bkng;
		$rent_interval      = empty( $_POST['bkng_product_datepicker'] ) ? $this->content['products'][ key( $this->content['products'] ) ]['rent_interval'] : array_map( 'strtotime', $_POST['bkng_product_datepicker'] );
		$product_quantities = array_map( 'absint', (array)$_POST['bkng_quantity'] );
		/**
		 * List of products IDs need to be updated
		 */
		$for_update = array();
		foreach( $this->content['products'] as $product_id => $data ) {
			$this->product_id = $product_id;
			$this->raw = array();

			/**
			 * Checks  whether the rent interval was changed
			 */
			if ( !! array_diff_assoc( $rent_interval, $data['rent_interval'] ) ) {
				$this->raw['rent_interval'] = $rent_interval;
				$data['rent_interval'] = $this->get_rent_interval();
			} else {
				/**
				 * Check whether the "for-sale"-products' quantity is changed
				 */
				$quantity = array_key_exists( $product_id, $product_quantities ) ? $product_quantities[ $product_id ] : false;

				/* don't do anything if we got no product quantity or if it is the same as before the order updating */
				if ( ! $quantity || $quantity == $data['quantity'] )
					continue;

				$this->raw['quantity'] = $quantity;
				$data['quantity'] = $this->get_quantity();
			}

			$for_update[] = $product_id;

			extract( $data );

			$subtotal = $this->get_product_subtotal( compact( 'rent_interval', 'quantity', 'price' ) );
			$total    = $this->get_product_total( $subtotal );
			$this->content['products'][ $product_id ] = compact( 'title', 'rent_interval', 'linked_to', 'quantity', 'price', 'subtotal', 'total' );
		}
		$this->count_order_total();
		return $for_update;
	}

	/**
	 * Saves the order data after the editing in the site admin panel
	 * @since    0.1
	 * @access   public
	 * @param    string           $new_status   The order new status
	 * @return   boolesn|string                 False in case of some errors, "saved" otherwise
	 */
	public function update_order( $new_status = '' ) {
		global $wpdb, $bws_bkng;

		$for_update = $this->check_products_changes();
		$new_status = empty( $new_status ) || ! in_array( $new_status, array_keys( $bws_bkng->get_order_statuses() ) ) || $this->content['status'] == $new_status ? false : $new_status;

		$update_fields = array();
		$result        = false;

		if ( $for_update ) {
			$update_fields['subtotal'] = $this->content['subtotal'];
			$update_fields['total']    = $this->content['total'];
		}

		if ( $new_status ) {
			$update_fields['status'] = $new_status;
			$this->content['status'] = $new_status;
			$result = 'status_changed';
		}

		/**
		 * Update order data
		 */
		if ( ! empty( $update_fields ) ) {
			$wpdb->update(
				BWS_BKNG_DB_PREFIX . $this->post_type . '_orders',
				$update_fields,
				array( 'id' => $this->order_id )
			);

			if ( $wpdb->last_error ) {
				$this->add_error( 'order_data_update' );
				return false;
			}
		}

		/**
		 * Update ordered products data
		 */
		if ( ! empty( $for_update ) ) {
			foreach( $for_update as $product_id ) {

				if ( empty( $this->content['products'][ $product_id ] ) )
					continue;

				$data = $this->content['products'][ $product_id ];
				$wpdb->update(
					BWS_BKNG_DB_PREFIX . $this->post_type . "_ordered_products",
					array(
						'rent_interval_from' => empty( $data['rent_interval']['from'] ) ? 0 : date( 'Y-m-d H:i:s', $data['rent_interval']['from'] ),
						'rent_interval_till' => empty( $data['rent_interval']['till'] ) ? 0 : date( 'Y-m-d H:i:s', $data['rent_interval']['till'] ),
						'quantity'           => $data['quantity'],
						'subtotal'           => $data['subtotal'],
						'total'              => $data['total']
					),
					array(
						'order_id'   => $this->order_id,
						'product_id' => $product_id,
					)
				);

				if ( $wpdb->last_error ) {
					$this->add_error( 'ordered_product_update' );
					break;
				}
			}
		}

		if ( $this->get_errors() ) {
			return false;
		} else {
			$hook_result = apply_filters( 'bws_bkng_update_order_result', true, $this->content );

			if ( is_wp_error( $hook_result ) ) {
				$this->add_error( 'error', $hook_result );
				return false;
			}
			return empty( $result ) ? 'saved' : $result;
		}
	}

	/**
	 * Fetch the billing data that were specified by the user during placing the order
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   array        format: {@see:self::$billing_data}
	 */
	public function get_billing_data() {
		global $bws_bkng;

		$billing_data = array_filter( (array)$this->billing_data );

		if ( ! $billing_data && ! $bws_bkng->is_admin() ) {
			$session = BWS_BKNG_Session::get_instance( true );
			$this->billing_data = $session->get( 'billing_data' );
		}

		if ( array_filter( (array)$this->billing_data ) )
			return $this->billing_data;

		$this->billing_data = array(
			'user_id'               => empty( $this->content['user_id'] ) ? '' : absint( $this->content['user_id'] ),
			'user_firstname'        => empty( $this->content['user_firstname'] ) ? '' : $this->content['user_firstname'],
			'user_lastname'         => empty( $this->content['user_lastname'] ) ? '' : $this->content['user_lastname'],
			'user_phone'            => empty( $this->content['user_phone'] ) ? '' : $this->content['user_phone'],
			'user_email'            => empty( $this->content['user_email'] ) ? '' : $this->content['user_email'],
			'user_message'          => empty( $this->content['user_message'] ) ? '' : $this->content['user_message'],
			'user_agree_with_terms' => empty( $this->content['user_agree_with_terms'] ) ? false : !! $this->content['user_agree_with_terms']
		);

		return $this->billing_data;
	}

	/**
	 * Checks whether there is a request to save the order
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   boolean
	 */
	public function is_place_order_query() {
		return isset( $_POST['bkng_place_order'] ) && ! empty( $_POST['bkng_billing_data'] ) && wp_verify_nonce( $_POST['bkng_nonce'], "bkng_place_order" );
	}

	/**
	 * Sanitize the data that were specified by the user on the checkout page before saving them.
	 * In case if user wanted to regisetr it also checks makes an attempt to register and login the user according to the specified data.
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   boolean         True - if the user enetered the correct data, false otherwise
	 */
	public function is_right_billing_data() {
		global $bws_bkng;

		$required = apply_filters( 'bws_bkng_required_bilings_fields', array(
			'user_firstname',
			'user_lastname',
			'user_email',
			'user_confirm_email'
		) );

		foreach ( $required as $field ) {
			if ( empty( $this->billing_data[ $field ] ) ) {
				$this->add_error( $field );
				return false;
			}
		}

		if ( in_array( 'user_email', $required ) && ! is_email( $this->billing_data['user_email'] ) ) {
			$this->add_error( 'wrong_user_email' );
			return false;
		}

		if ( in_array( 'user_confirm_email', $required ) && 0 !== strcmp( $this->billing_data['user_email'], $this->billing_data['user_confirm_email'] ) ) {
			$this->add_error( 'missmatch_user_email' );
			return false;
		}

		if ( in_array( 'user_phone', $required ) && ! $bws_bkng->is_valid_phone( $this->billing_data['user_phone'] ) ) {
			$this->add_error( 'wrong_user_phone' );
			return false;
		}

		if ( bws_bkng_get_terms_and_conditions() && ! $this->billing_data['user_agree_with_terms'] ) {
			$this->add_error( 'user_agree_with_terms' );
			return false;
		}

		$error = apply_filters( 'bws_bkng_check_billing_data', true, $this->billing_data );

		if ( true !== $error ) {
			$this->add_error( strval( $error ) );
			return false;
		}

		/**
		 * Register new user
		 */
		if ( ! empty( $_POST['bkng_billing_data']['register_user'] ) ) {

			if ( ! get_option( 'users_can_register' ) )
				die( __( 'Oops, something went wrong', BWS_BKNG_TEXT_DOMAIN ) );

			$user = get_user_by( 'email', $this->billing_data['user_email'] );

			if ( $user instanceof WP_User ) {
				$this->add_error( 'user_exists_signon' );
				return false;
			}

			$user_data = array(
				'user_login' => sanitize_user( $this->billing_data['user_firstname'], true ),
				'user_pass'  => wp_generate_password(),
				'user_email' => $this->billing_data['user_email'],
				'first_name' => $this->billing_data['user_firstname'],
				'last_name'  => $this->billing_data['user_lastname'],
				'role'       => 'bws_bkng_customer'
			);

			if ( empty( $user_data['user_login'] ) || username_exists( $user_data['user_login'] ) )
				$user_data['user_login'] = $user_data['user_email'];

			$user_id = wp_insert_user( $user_data );

			if ( is_wp_error( $user_id ) ) {
				$this->add_error( 'can_not_register_user' );
				return false;
			}

			if ( in_array( 'user_phone', $required ) )
				update_user_meta( $user_id, 'user_phone', $this->billing_data['user_phone'] );

			wp_new_user_notification( $user_id, null, 'both' );

			do_action( 'bws_bkng_after_user_creation', $this->billing_data );

			$credentials = array(
				'user_login'    => $user_data['user_login'],
				'user_password' => $user_data['user_pass'],
				'remember'      => true
			);

			$user = wp_signon( $credentials, false );

			if ( is_wp_error( $user ) ) {
				$this->add_error( 'can_not_signon_user' );
				return false;
			}

			$this->billing_data['user_id'] = $user_id;
		/* Update user meta data */
		} elseif( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), 'user_phone', $this->billing_data['user_phone'] );
			do_action( 'bws_bkng_update_user_data', $this->billing_data );
		}

		return true;
	}

	/**
	 * Saves orders to the database
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   boolean         True - if the order was saved successfully, false otherwise
	 */
	public function place_order() {
		global $wpdb, $bws_bkng;

		if ( empty( $this->billing_data ) || empty( $this->content ) ) {
			$this->add_error( 'empty_order' );
			return false;
		}

		/**
		 * Add order data
		 */
		$now = current_time( 'mysql' );
		$wpdb->insert(
			BWS_BKNG_DB_PREFIX . $this->post_type . '_orders',
			array(
				'status'         => 'on_hold',
				'date_create'    => $now,
				'user_id'        => $this->billing_data['user_id'],
				'user_firstname' => $this->billing_data['user_firstname'],
				'user_lastname'  => $this->billing_data['user_lastname'],
				'user_email'     => $this->billing_data['user_email'],
				'user_phone'     => $this->billing_data['user_phone'],
				'user_message'   => $this->billing_data['user_message'],
				'subtotal'       => $this->content['subtotal'],
				'total'          => $this->content['total']
			),
			array( '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%f' )
		);

		if ( $wpdb->last_error ) {
			$this->add_error( 'database_error' );
			return false;
		}
		$this->content['status']      = 'on_hold';
		$this->content['date_create'] = $now;
		$this->content = array_merge( $this->content, $this->billing_data );

		$order_id = $wpdb->insert_id;

		$order_meta = array();

		if ( $this->billing_data['user_agree_with_terms'] ) {
			$order_meta[] = "( {$order_id}, 'user_agree_with_terms', '1' )";
		}

		/**
		 * Save the additional data actual for the placing order moment
		 * in order to restore them during the order viewing in archives
		 */
		$currency     = $bws_bkng->get_option( 'currency_code' );
		$order_meta[] = "( " . $order_id . ", 'currency_code', '" . $currency . "' )";
		$currency_position = $bws_bkng->get_option( 'currency_position' );
		$order_meta[]      = "( " . $order_id . ", 'currency_position', '" . $currency_position . "' )";
		$order_meta        = apply_filters( 'bws_bkng_order_meta', $order_meta, $order_id, $this->content, $this->billing_data );
		$insert_data       = implode( ',', $order_meta );

		$table = BWS_BKNG_DB_PREFIX . $this->post_type . '_orders_meta';
		$wpdb->query(
		    $wpdb->prepare(
		        "INSERT INTO `{$table}`
				( `order_id`, `meta_key`, `meta_value` )
			    VALUES {$insert_data};"
            )
		);

		/*
		 * Add products data
		 */
		$products_data = array();
		foreach( $this->content['products'] as $product_id => $data ) {

			$wpdb->insert(
				BWS_BKNG_DB_PREFIX . $this->post_type . '_ordered_products',
				array(
					'order_id'           => $order_id,
					'product_id'         => $product_id,
					'linked_to'          => $data['linked_to'] ? $data['linked_to'] : 0,
					'rent_interval_from' => date( 'Y-m-d H:i:s', $data['rent_interval']['from'] ),
					'rent_interval_till' => date( 'Y-m-d H:i:s', $data['rent_interval']['till'] ),
					'rent_interval_step' => $data['rent_interval']['step'],
					'quantity'           => $data['quantity'],
					'price'              => $data['price'],
					'subtotal'           => $data['subtotal'],
					'total'              => $data['total']
				)
			);
		}

		do_action( 'bws_bkng_after_order_placed', $order_id, $this->content, $this->billing_data );

		return $order_id;
	}

	/**
	 * Fetch the error message accrording to the given error codes
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   object|false         An instance of the class WP_Error in case if some errors occurred, false otherwise
	 */
	public function get_errors() {

		if ( empty( $this->errors ) )
			return false;

		$errors = new WP_Error();

		foreach( $this->errors as $code => $data ) {

			if ( is_wp_error( $data ) ) {

				$message = $data->get_error_message();
				$data    = $data->get_error_data();

			} else {
				switch( $code ) {
					case 'user_firstname':
					case 'user_lastname':
					case 'user_phone':
					case 'user_email':
					case 'user_confirm_email':
						$message = __( 'Please fill in all required fields', BWS_BKNG_TEXT_DOMAIN );
						break;
					case 'wrong_user_phone':
						$message = __( 'Please enter the correct phone number', BWS_BKNG_TEXT_DOMAIN );
						break;
					case 'wrong_user_email':
						$message = __( 'Please enter the correct email', BWS_BKNG_TEXT_DOMAIN );
						break;
					case 'missmatch_user_emails':
						$message = __( 'Emails don\'t match. Please check', BWS_BKNG_TEXT_DOMAIN );
						break;
					case 'user_agree_with_terms':
						$message = __( 'Please accept Terms and Conditions', BWS_BKNG_TEXT_DOMAIN );
						break;
					case 'database_error':
						$message = __( 'Cannot place order. Please try again later', BWS_BKNG_TEXT_DOMAIN );
						break;
					case 'empty_order':
						$message = __( 'It seems that there is nothing to order. Please choose a product to make an order', BWS_BKNG_TEXT_DOMAIN );
						break;
					case 'user_exists_signon':
						$message = sprintf(
							__( 'User with such email is already registered. Would you like to %s', BWS_BKNG_TEXT_DOMAIN ) . '?',
							'<a href="' . wp_login_url( bws_bkng_get_page_permalink( 'checkout' ) ) . '">' . __( 'login', BWS_BKNG_TEXT_DOMAIN ) . '</a>'
						);
						break;
					case 'can_not_register_user':
						$message = __( 'Cannot register new user. Please try again later', BWS_BKNG_TEXT_DOMAIN );
						break;
					case 'can_not_signon_user':
						$message = __( 'Cannot authenticate user', BWS_BKNG_TEXT_DOMAIN );
						break;
					case 'order_data_update':
						$message = __( 'Cannot update order data', BWS_BKNG_TEXT_DOMAIN );
						break;
					case 'ordered_product_update':
						$message = __( 'Cannot update products list', BWS_BKNG_TEXT_DOMAIN );
						break;
					default:
						$message = apply_filters( 'bws_bkng_order_errors', '', $code );
						break;
				}
			}


			if ( ! empty( $message ) )
				$errors->add( $code, $message, $data );
		}

		return $errors;
	}

	/**
	 * Forms the data of each product in the order before following handlings
	 * @since    0.1
	 * @access   protected
	 * @param    void
	 * @return   array        The product's data
	 */
	protected function prepare() {
		$linked_to     = empty( $this->raw['linked_to'] ) ? false : absint( $this->raw['linked_to'] );
		$title         = get_the_title( $this->product_id );
		$sku           = get_post_meta( $this->product_id, 'bkng_sku', true );
		$rent_interval = $this->get_rent_interval();
		$quantity      = $this->get_quantity();
		$price         = abs( floatval( bws_bkng_get_product_price( $this->product_id ) ) );
		$subtotal      = $this->get_product_subtotal( compact( 'rent_interval', 'quantity', 'price' ) );
		$total         = $this->get_product_total( $subtotal );

		return compact( 'title', 'sku', 'rent_interval', 'linked_to', 'quantity', 'price', 'subtotal', 'total' );
	}

	/**
	 * Class constructor
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   void
	 */
	private function __construct() {
		global $bws_bkng, $plugin_page;

		if ( ! $bws_bkng->is_admin() ) {
			$session = BWS_BKNG_Session::get_instance( true );
			$this->post_type = isset( $_REQUEST['post_type'] ) ? sanitize_text_field( stripslashes( $_REQUEST['post_type'] ) ) : get_query_var( 'post_type' );
		}

		/*
		 * To get order details on Dashboard
		 */
		if ( $bws_bkng->is_admin() ) {

			$this->post_type = sanitize_text_field( stripslashes( $_REQUEST['post_type'] ) );

			if ( 'bkrntl_bws_bike_orders' === $plugin_page ) {
				$order_id = absint( $_GET['bkng_order_id'] );

				if ( empty( $order_id ) )
					die( __( 'Oops, something went wrong', BWS_BKNG_TEXT_DOMAIN ) );

				$this->order_id = $order_id;
				$this->content  = $this->get( $this->order_id );
			}

			return;

		/*
		 * Conversion by the click on the checkout button on single products page
		 */
		} elseif( ! empty( $_POST['bkng_checkout_product'] ) ) {

			if ( empty( $_POST['bkng_product'] ) || ! wp_verify_nonce( $_POST['bkng_nonce'], "bkng_checkout_product" ) )
				die( __( 'Oops, something went wrong', BWS_BKNG_TEXT_DOMAIN ) );


			$this->rent_interval = array(
				'from' => empty( $_POST['bws_bkng_search']['from'] ) ? '' : ( $bws_bkng->is_valid_date( $_POST['bws_bkng_search']['from'] ) ? strtotime( $_POST['bws_bkng_search']['from'] ) : absint( $_POST['bws_bkng_search']['from'] ) ),
				'till' => empty( $_POST['bws_bkng_search']['till'] ) ? '' : ( $bws_bkng->is_valid_date( $_POST['bws_bkng_search']['till'] ) ? strtotime( $_POST['bws_bkng_search']['till'] ) : absint( $_POST['bws_bkng_search']['till'] ) )
			);

			if ( ! array_filter( $this->rent_interval ) )
				$this->rent_interval = bws_bkng_get_session_rent_interval();

			$main_product = absint( $_POST['bkng_product'] );

			$this->post_type = get_post_type( $main_product );

			$data = array(
				$main_product => array(
					'rent_interval' => $this->rent_interval,
					'quantity'      => empty( $_POST['bkng_quantity'] ) ? '' : absint( $_POST['bkng_quantity'] ),
					'linked_to'     => false
				)
			);

			if ( ! empty( $_POST['bkng_extras'] ) ) {
				foreach( (array)$_POST['bkng_extras'] as $id => $raw_data ) {

					if ( empty( $raw_data['choose'] ) )
						continue;

					$data[ $id ] = array(
						'rent_interval' => $this->rent_interval,
						'quantity'      => empty( $raw_data['quantity'] ) ? false : absint( $raw_data['quantity'] ),
						'linked_to'     => $main_product
					);
				}
			}
		/*
		 * Get from session
		 */
		} elseif ( $session->get( 'order' ) ) {
			$data = $session->get( 'order' );
			if ( ! empty( $data ) ) {
				$this->content = apply_filters( 'bws_bkng_order_content', $data );
				$skip_order_data_prepare = true;
				$main_product = key( $this->content['products'] );
				$this->post_type = get_post_type( $main_product );
			}

		/*
		 * In any other cases
		 * Get data from the cart
		 */
		} else {
			$data = $this->get_from_cart();
		}

		if ( empty( $data ) )
			return false;

		if ( empty( $skip_order_data_prepare ) ) {

			foreach( $data as $id => $raw_data ) {

				$this->product_id = absint( $id );
				$this->raw        = $raw_data;

				$this->content['products'][ $this->product_id ] = $this->prepare();
			}

			$this->count_order_total();

			$this->content = apply_filters( 'bws_bkng_order_content', $this->content );
		}

		/**
		 * Customers billing data
		 */
		if ( $this->is_place_order_query() ) {
			if ( empty( $_POST['bkng_billing_data'] ) )
				die( __( 'Oops, something went wrong', BWS_BKNG_TEXT_DOMAIN ) );

			$billing_data = array_map( 'sanitize_text_field', (array)$_POST['bkng_billing_data'] );

			$this->billing_data['user_id']               = get_current_user_id();
			$this->billing_data['user_firstname']        = empty( $billing_data['user_firstname'] )     ? '' : sanitize_user( $billing_data['user_firstname'] );
			$this->billing_data['user_lastname']         = empty( $billing_data['user_lastname'] )      ? '' : sanitize_user( $billing_data['user_lastname'] );
			$this->billing_data['user_phone']            = empty( $billing_data['user_phone'] )         ? '' : sanitize_text_field( $billing_data['user_phone'] );
			$this->billing_data['user_email']            = empty( $billing_data['user_email'] )         ? '' : sanitize_email( $billing_data['user_email'] );
			$this->billing_data['user_confirm_email']    = empty( $billing_data['user_confirm_email'] ) ? '' : sanitize_email( $billing_data['user_confirm_email'] );
			$this->billing_data['user_message']          = empty( $billing_data['user_message'] )       ? '' : esc_textarea( $billing_data['user_message'] );
			$this->billing_data['user_agree_with_terms'] = ! empty( $billing_data['user_agree_with_terms'] );

		} elseif ( array_filter( (array)$session->get( 'billing_data' ) ) ) {

			$this->billing_data = $session->get( 'billing_data' );

		} else {

			$current_user = wp_get_current_user();

			$this->billing_data['user_id']               = $current_user->ID;
			$this->billing_data['user_firstname']        = empty( $current_user->user_firstname ) ? '' : $current_user->user_firstname;
			$this->billing_data['user_lastname']         = empty( $current_user->user_lastname )  ? '' : $current_user->user_lastname;

			$this->billing_data['user_email']            = empty( $current_user->user_email ) ? '' : $current_user->user_email;
			$this->billing_data['user_confirm_email']    = empty( $current_user->user_email ) ? '' : $current_user->user_email;

			$this->billing_data['user_phone']            = empty( $current_user->user_phone ) ? '' : $current_user->user_phone;
			$this->billing_data['user_message']          = '';
			$this->billing_data['user_agree_with_terms'] = false;
		}

		$this->billing_data = apply_filters( 'bws_bkng_billing_data', $this->billing_data );
	}

	/**
	 * Prepares the order data before  displaying the order data in notifications.
	 * For displaying of the oreder data the class BWS_BKNG_Single_Order_Products_List is used.
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   array
	 */
	public function prepare_to_display() {
		global $bws_bkng, $wpdb;
		$data                 = $this->content;
		$currency             = bws_bkng_get_currency();
		$currency_position    = bws_bkng_get_currency_position();
		$date_dormat          = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$this->show_summaries = $this->show_summaries( array_keys( $data['products'] ) );

		foreach( $data['products'] as $id => $raw_data ) {

			$price         = bws_bkng_number_format( $raw_data['price'] );
			$rent_interval = bws_bkng_get_rent_interval( $id );

			if ( empty( $raw_data['linked_to'] ) )
				$data['products'][ $id ]['title'] = "<strong>{$raw_data['title']}</strong>";
			else
				$data['products'][ $id ]['title'] = "-&nbsp;{$raw_data['title']}";

			$data['products'][ $id ]['sku'] = empty( $raw_data['sku'] ) ? "#{$id}" : $raw_data['sku'];

			if ( get_post_meta( $id, 'bkng_price_on_request', true ) ) {
				$data['products'][ $id ]['price'] = '-';
			} else {
				$data['products'][ $id ]['price']  = 'left' == $currency_position ? "{$currency}{$price}" : "{$price}{$currency}";
				$data['products'][ $id ]['price'] .= empty( $rent_interval ) ? '' : "&nbsp;{$rent_interval}";
			}

			$data['products'][ $id ]['quantity'] = $raw_data['quantity'];
			if ( $this->show_summaries ) {
				/**
				 * @see self::get_columns() comment with the mark "for_next_update"
				 * $subtotal = bws_bkng_number_format( $raw_data['subtotal'] );
				 * $data['products'][ $id ]['subtotal'] = 'left' == $currency_position ? "{$currency}{$subtotal}" : "{$subtotal}{$currency}";
				 */
				$total = bws_bkng_number_format( $raw_data['total'] );
				$data['products'][ $id ]['total'] = 'left' == $currency_position ? "{$currency}{$total}" : "{$total}{$currency}";
			}
		}

		if ( $this->show_summaries ) {
			$subtotal = bws_bkng_number_format( $data['subtotal'] );
			$total    = bws_bkng_number_format( $data['total'] );
			if ( 'left' == $currency_position ) {
				$data['subtotal'] = "{$currency}{$subtotal}";
				$data['total']    = "{$currency}{$total}";
			} else {
				$data['subtotal'] = "{$subtotal}{$currency}";
				$data['total']    = "{$total}{$currency}";
			}
		} else {
			unset( $data['subtotal'], $data['total'] );
		}

		return apply_filters( 'bws_bkng_order_table_data', $data );
	}

	/**
	 * Fetch the list of table columns for the further displaying the order data in notifications.
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   array
	 */
	public function get_columns() {
		$columns = array(
			'title'         => __( 'Product', BWS_BKNG_TEXT_DOMAIN ),
			'sku'           => __( 'SKU', BWS_BKNG_TEXT_DOMAIN ),
			'price'         => __( 'Price', BWS_BKNG_TEXT_DOMAIN ),
			'quantity'      => __( 'Quantity', BWS_BKNG_TEXT_DOMAIN )
		);

		if ( $this->show_summaries ) {
			/**
			 * Temporary commented due to the lack of functionality
			 * of accrual of additional taxes or sales for each product.
			 * @todo: Uncomment after the appropriate functionality will be implemented to the Booking core
			 *
			 * $columns['subtotal'] = __( 'Subtotal', BWS_BKNG_TEXT_DOMAIN );
			 */
			$columns['total'] = __( 'Summary', BWS_BKNG_TEXT_DOMAIN );
		}
		return apply_filters( 'bws_bkng_order_table_columns', $columns );
	}

	/**
	 * Fetch the products that were added to the cart-storage
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   array
	 */
	private function get_from_cart() {
		// $cart = BWS_BKNG_Cart::get_instance();
		// $data = $cart->get();
		// return $data['products'];
	}

	public function get_order_post_type(){
		return $this->post_type;
	}

	/**
	 * Some magic to avoid the creation of several instances of this class
	 * @since  0.1
	 */
	private function __clone()     {}
	private function __sleep()     {}
	private function __wakeup()    {}

}