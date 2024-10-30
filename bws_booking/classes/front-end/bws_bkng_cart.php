<?php
/**
 * Handles cart data
 *
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Cart' ) )
	return;

/**
 * Default Cart content structure
 * @var array
 * array(
 * 	products => array(
 * 		{product_id} => array(
 * 			rent_interval => @var array('from'=>{int/unix_timestamp},'till'={int/unix_timestamp})|false  Array - for for-rent-products, false otherwise
 * 			quantity      => @var int|false    int - for for-sale-products, false otherwise,
 * 			linked_to     => @var int|false    int - main products id, false otherwise (if it is the main product),
 * 		),
 * 		{product_id} => array( ... ),
 * 		{product_id} => array( ... ),
 * 		{product_id} => array( ... ),
 * 		...
 * 	)
 * )
 */

class BWS_BKNG_Cart extends BWS_BKNG_Order_Handler {

	/**
	 * Contains an instance of the current class
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    object
	 */
	private static $instance;

	/**
	 * Contains an instance of class that handle the data storage
	 * depending on whether the current user is logged on the site or not.
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    object
	 */
	private $storage;

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
	 * Prepares products data and saving them to the storage
	 * @since    0.1
	 * @access   public
	 * @param    array           The list of products that need to add to the cart
	 * @return   object|array    The list of newly added products in case of success, the instance of class WP_Error otherwise
	 */
	public function add( $data ) {

		if ( empty( $data ) )
			return false;

		$to_add = array();

		foreach ( (array)$data as $id => $raw_data ) {

			$this->product_id = absint( $id );
			$this->raw = $raw_data;
			if ( empty( $this->product_id ) || ! $this->is_available( $this->product_id ) ) {
				$this->add_error( 'not_enough_data' );
				continue;
			}
			if ( $this->is_in_cart( $this->product_id ) ) {
				$this->add_error( 'already_in' );
				continue;
			}
			$this->content['products'][ $this->product_id ] = $this->prepare();
			$to_add[] = $this->product_id;
		}
		if ( ! empty( $to_add ) ) {
			$this->update_storage();
		}

		return empty( $to_add ) ? $this->get_errors() : $to_add;
	}

	/**
	 * Removes selected product from the cart
	 * @since    0.1
	 * @access   public
	 * @param    int     $product_id   The product id
	 * @return   boolean               True - if the product was removed, false otherwise
	 */
	public function remove( $product_id ) {

		$this->product_id = absint( $product_id );

		if ( empty( $this->product_id ) || ! $this->is_in_cart( $product_id ) )
			return false;

		unset( $this->content['products'][ $product_id ] );

		$this->update_storage();

		return true;
	}

	/**
	 * Removes all data from the cart
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function clear() {
		$this->storage->remove( 'cart' );
	}

	/**
	 * Unites the cart data from local storage (cookies) and db, after the user's authentication
	 * @see BWS_BKNG_Session::get_instance(), filter 'bws_bkng_merge_storage' definition
	 * @since    0.1
	 * @access   public
	 * @param    array     $db_data       The cart data from the database
	 * @param    array     $cookie_data   The cart data from the browser cookies
	 * @param    string    $key           The cart data from the browser cookies
	 * @return   array                    The merged cart data
	 */
	public function merge( $db_data, $cookie_data, $key ) {

		if ( 'cart' != $key )
			return $cookie_data;

		$this->content = $db_data;

		foreach ( $cookie_data['products'] as $product_id => $raw ) {

			if ( $this->is_in_cart( $product_id ) )
				continue;

			$data = array(
				'rent_interval' => empty( $raw['rent_interval'] ) ? false : array_map( 'absint', (array)$raw['rent_interval'] ),
				'linked_to'     => empty( $raw['linked_to'] ) ? false : array_map( 'absint', (array)$raw['linked_to'] ),
				'quantity'      => empty( $raw['quantity'] )  ? false : absint( $raw['quantity'] )
			);

			$this->content['products'][ $product_id ] = $data;
		}

		$this->content = apply_filters( 'bws_bkng_cart_content_merged', $this->content, $db_data, $cookie_data );

		return $this->content;
	}

	/**
	 * Checks whether the given product is in the cart
	 * @since    0.1
	 * @access   public
	 * @param    int     $product_id       The  product ID
	 * @return   boolean
	 */
	public function is_in_cart( $product_id ) {
		return empty( $this->content['products'] ) ? false : array_key_exists( absint( $product_id ), $this->content['products'] );
	}

	/**
	 * Prepares the given product data before saving it to the cart
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   object|false
	 */
	public function get_errors() {

		if ( empty( $this->errors ) )
			return false;

		$errors = new WP_Error();

		foreach( $this->errors as $code => $data ) {
			switch( $code ) {
				case 'not_enough_data':
					$message = __( 'Not enough data to add product(-s) to the cart', BWS_BKNG_TEXT_DOMAIN );
					break;
				case 'already_in':
					$message = _n( 'The product is already in the cart', 'Products are already in the cart', count( $data ), BWS_BKNG_TEXT_DOMAIN );
					break;
				default:
					$message = apply_filters( 'bws_bkng_cart_errors', '', $code );
					break;
			}

			if ( ! empty( $message ) )
				$errors->add( $code, $message, $data );
		}

		return $errors;
	}

	/**
	 * Prepares the each product's data before displaying
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   array    The products data list
	 */
	public function prepare_to_display() {
		global $bws_bkng;

		$data              = $this->content;
		$currency          = bws_bkng_get_currency();
		$currency_position = bws_bkng_get_currency_position();
		$date_dormat       = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		/* Add necessary data to products */
		foreach( $data['products'] as $id => $raw_data ) {
			$is_for_sale   = 'sale' == get_post_meta( $id, 'bkng_product_for', true );
			$title         = get_the_title( $id );
			$sku           = get_post_meta( $id, 'bkng_sku', true );
			$price         = abs( floatval( bws_bkng_get_product_price( $id ) ) );
			$rent_interval = $raw_data['rent_interval'];
			$quantity      = $raw_data['quantity'];
			$subtotal      = $this->get_product_subtotal( compact( 'is_for_sale', 'rent_interval', 'quantity', 'price' ) );
			$total         = $this->get_product_total( $subtotal );

			$data['products'][ $id ] += compact( 'title', 'sku', 'price', 'subtotal', 'total', 'is_for_sale' );
		}

		$cost = $this->count_order_total( $data['products'], true );

		/* add JS-object in order to handle the products cart */
		wp_localize_script( 'bkng_front_script', 'bws_bkng_cart', $data );

		$wrap = '<span class="bws_bkng_product_%1$s_%2$s">%3$s</span>';

		foreach( $data['products'] as $id => $raw_data ) {

			$price    = sprintf( $wrap, $id, 'price', bws_bkng_number_format( $raw_data['price'] ) );
			$subtotal = sprintf( $wrap, $id, 'subtotal', bws_bkng_number_format( $raw_data['subtotal'] ) );
			$total    = sprintf( $wrap, $id, 'total', bws_bkng_number_format( $raw_data['total'] ) );

			if ( 'left' == $currency_position ) {
				$data['products'][ $id ]['price']    = "{$currency}{$price}";
				$data['products'][ $id ]['subtotal'] = "{$currency}{$subtotal}";
				$data['products'][ $id ]['total']    = "{$currency}{$total}";
			} else {
				$data['products'][ $id ]['price']    = "{$price}{$currency}";
				$data['products'][ $id ]['subtotal'] = "{$subtotal}{$currency}";
				$data['products'][ $id ]['total']    = "{$total}{$currency}";
			}

			if ( $raw_data['is_for_sale'] ) {
				/*
				 * The product quantity input
				 */
				$name  = "bkng_cart[{$id}][quantity]";
				$value = $raw_data['quantity'];
				$max   = get_post_meta( $id, 'bkng_in_stock', true );
				$min   = 1;

				if ( $value > $max )
					$value = $max;

				$data['products'][ $id ]['quantity'] = $bws_bkng->get_number_input( compact( 'name', 'min', 'max', 'value' ) );
				$data['products'][ $id ]['pick_up']  = '';
				$data['products'][ $id ]['drop_off'] = '';
			} else {
				$data['products'][ $id ]['quantity'] = '';

				/*
				 * Rent interval input
				 */
				$data['products'][ $id ]['pick_up'] = bws_bkng_datetimepicker(
					'from',
					array(
						'label' => '',
						'value' => $raw_data['rent_interval']['from']
					),
					"bkng_cart[{$id}][rent_interval][%s]",
					true
				);

				$data['products'][ $id ]['drop_off'] = bws_bkng_datetimepicker(
					'till',
					array(
						'label' => '',
						'value' => $raw_data['rent_interval']['till']
					),
					"bkng_cart[{$id}][rent_interval][%s]",
					true
				);
			}

			$data['products'][ $id ]['price'] .= empty( $raw_data['rent_interval'] ) ? '' : '&nbsp;' . bws_bkng_get_rent_interval( $id );

			if ( empty( $raw_data['linked_to'] ) )
				$data['products'][ $id ]['title'] = "<strong>{$raw_data['title']}</strong>";
			else
				$data['products'][ $id ]['title'] = "-&nbsp;{$raw_data['title']}";

			$name  = "bkng_delete_from_cart[]";
			$value = $id;
			$noscript_checkbox = $bws_bkng->get_checkbox( compact( 'name', 'value' ) );

			$data['products'][ $id ]['delete'] = "<a data-product=\"{$id}\" data-main-product=\"{$raw_data['linked_to']}\" href=\"#\" class=\"bws_bkng_delete_from_car_link dashicons dashicons-trash bkng_hide_if_no_js\"></a><noscript>{$noscript_checkbox}</noscript>";
		}

		$wrap     = '<span class="bws_bkng_cart_%1$s">%2$s</span>';
		$subtotal = sprintf( $wrap, 'subtotal', bws_bkng_number_format( $cost['subtotal'] ) );
		$total    = sprintf( $wrap, 'total', bws_bkng_number_format( $cost['total'] ) );

		if ( 'left' == $currency_position ) {
			$data['subtotal'] = "{$currency}{$subtotal}";
			$data['total']    = "{$currency}{$total}";
		} else {
			$data['subtotal'] = "{$subtotal}{$currency}";
			$data['total']    = "{$total}{$currency}";
		}

		return apply_filters( 'bws_bkng_order_table_data', $data );
	}

	/**
	 * Fetch the list of table columns for the following displaying of the cart data
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   array   The list of columns in format {column_slug} => {column_title}
	 */
	public function get_columns() {
		$columns = array(
			'title'         => __( 'Title', BWS_BKNG_TEXT_DOMAIN ),
			'sku'           => __( 'SKU', BWS_BKNG_TEXT_DOMAIN ),
			'pick_up'       => __( 'Pick-Up Date', BWS_BKNG_TEXT_DOMAIN ),
			'drop_off'      => __( 'Drop-Off Date', BWS_BKNG_TEXT_DOMAIN ),
			'price'         => __( 'Price', BWS_BKNG_TEXT_DOMAIN ),
			'quantity'      => __( 'Quantity', BWS_BKNG_TEXT_DOMAIN ),
			'subtotal'      => __( 'Subtotal', BWS_BKNG_TEXT_DOMAIN ),
			'total'         => __( 'Cost', BWS_BKNG_TEXT_DOMAIN ),
			'delete'        => '<span class="dashicons dashicons-trash"></span>'
		);
		return apply_filters( 'bws_bkng_order_table_columns', $columns );
	}

	/**
	 * Prepares the given product data before saving it to the cart
	 * @since    0.1
	 * @access   protected
	 * @param    void
	 * @return   array
	 */
	protected function prepare() {
		$is_for_sale   = 'sale' == get_post_meta( $this->product_id, 'bkng_product_for', true );
		$linked_to     = empty( $this->raw['linked_to'] ) ? false : absint( $this->raw['linked_to'] );
		$rent_interval = $this->get_rent_interval( $is_for_sale );
		$quantity      = $this->get_quantity( $is_for_sale, $linked_to );

		return compact( 'rent_interval', 'linked_to', 'quantity' );
	}

	/**
	 * Checks whether the given product is available for rent ( sale )
	 * @since    0.1
	 * @access   private
	 * @param    int     $product_id       The  product ID
	 * @return   boolean
	 */
	private function is_available( $product_id ) {
		global $wpdb;
		
		$field_ids_table = BWS_BKNG_DB_PREFIX . 'bws_bike_field_ids';
		$field_post_data_table = BWS_BKNG_DB_PREFIX . 'bws_bike_field_post_data';

		$is_countable = $wpdb->get_var(
		    $wpdb->prepare(
		        'SELECT ' . $field_post_data_table . '.post_value FROM ' . $field_post_data_table . '
				LEFT JOIN ' . $field_ids_table . ' ON ' . $field_ids_table . '.field_id = ' . $field_post_data_table . '.field_id
				AND ' . $field_ids_table . '.field_slug = "bkrntl_quantity_available"
				AND ' . $field_post_data_table . '.post_id = %d;',
                $product_id
            )
        );
		return 'available' == $is_countable;
	}

	/**
	 * Fully updates the cart storage after the handling the products data
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   void
	 */
	private function update_storage() {
		global $bws_bkng;

		$this->content = apply_filters( 'bws_bkng_cart_before_save', $this->content );

		if ( $this->storage->update( 'cart', $this->content ) && ! wp_next_scheduled( 'bws_bkng_clear_cart' ) )
			wp_schedule_event( time(), 'bws_bkng_keep_goods_in_cart', 'bws_bkng_clear_cart' );
	}

	/**
	 * Classs constructor
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   void
	 */
	private function __construct() {

		add_filter( 'bws_bkng_merge_storage', array( $this, 'merge' ), 10, 3 );

		$this->storage = BWS_BKNG_Session::get_instance();
		$this->content = $this->storage->get( 'cart' );
	}

	/**
	 * Adds extras to the cart
	 * @uses   On the product single page in the site front-end
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function bkng_add_extras_to_cart() {
		global $bws_booking_adapter;

		if ( empty( $_POST['bkng_product'] ) ) {
			$this->errors = new WP_Error( 'add_to_cart_error', __( 'Cannot add extras to the cart', BWS_BKNG_TEXT_DOMAIN ) );
			die();
		}
		$data          = array();
		$rent_interval = array(
			'from' => empty( $_POST['bws_bkng_search']['from'] ) ? '' : sanitize_text_field( stripslashes( $_POST['bws_bkng_search']['from'] ) ),
			'till' => empty( $_POST['bws_bkng_search']['till'] ) ? '' : sanitize_text_field( stripslashes( $_POST['bws_bkng_search']['till'] ) )
		);
		$main_product  = absint( $_POST['bkng_product'] );

		$extras = $bws_booking_adapter->get_available_extra( $main_product );
		$cart = BWS_BKNG_Cart::get_instance();

		if( ! empty( $extras ) ) {
			foreach ( $extras as $post ) {
				if( $cart->is_in_cart( $post->ID ) ) {
					$cart->remove( $post->ID );
				}
			}
		}

		if( ! empty( $_POST['bkng_extras'] ) ) {
			foreach( $_POST['bkng_extras'] as $key => $raw ) {
				
				if ( empty( $key ) )
					continue;

				$data[ $key ] = array(
					'rent_interval' => $rent_interval,
					'quantity'      => empty( $raw['choose'] ) ? false : absint( $raw['choose'] )
				);
			}
		}

		$cart = BWS_BKNG_Cart::get_instance();
		
		if ( ! $cart->is_in_cart( $main_product ) ) {
			$data = array(
				$main_product => array(
					'rent_interval' => $rent_interval,
					'quantity'      => 1,
					'linked_to'     => false
				)
			) + $data;
		}
		
		$cart->add( $data );
	}


	/**
	 * Some magic to avoid the creation of several instances of this class
	 * @since  0.1
	 */
	private function __clone()     {}
	private function __sleep()     {}
	private function __wakeup()    {}

}