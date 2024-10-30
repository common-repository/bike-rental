<?php
/**
 * Abstract class that are used to generate order details before displaying them
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Order_Handler' ) )
	return;

abstract class BWS_BKNG_Order_Handler {

	/**
	 * Contains the list of product and additional order data
	 * @since    0.1
	 * @access   protected
	 * @var      array
	 */
	protected $content = array();

	/**
	 * The current handled product ID
	 * @since    0.1
	 * @access   protected
	 * @var      int
	 */
	protected $product_id;

	/**
	 * The list of products' data before handling
	 * @since    0.1
	 * @access   protected
	 * @var      int
	 */
	protected $raw = array();

	/**
	 * The list of errors codes
	 * @since    0.1
	 * @access   protected
	 * @var      array
	 */
	protected $errors = array();

	/**
	 * Wheteher to show order summaries
	 * @since    0.1
	 * @access   protected
	 * @var      boolean
	 */
	protected $show_summaries;

	/**
	 * Fetch the order content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   array
	 */
	public function get() {
		return $this->content;
	}

	/**
	 * Get the list of ordered products in table format
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function table( $return = false, $for_mail = false ) {
		$this->sort_products();
		$data    = $this->prepare_to_display();
		$columns = $this->get_columns();
		$table   = new BWS_BKNG_Order_Table( $data, $columns );
		if ( $return )
			return $table->get( $for_mail );
		else
			$table->display();
	}

	/**
	 * Fetch the list of table columns for the following displaying
	 * @since    0.1
	 * @abstract
	 * @access   public
	 * @param    void
	 * @return   object|false   The instance of the class WP_Error in case if some errors occurred, false otherwise
	 */
	abstract public function get_errors();

	/**
	 * Adds error code to the array of codes.
	 * An ability to get the error message depending on the given error code
	 * have to be implemented in the get_errors() methods of child classes
	 * @since    0.1
	 * @access   protected
	 * @param    string    $code
	 * @param    mixed     $data
	 * @return   array
	 */
	protected function add_error( $code, $data = '' ) {

		if ( empty( $this->errors[ $code ] ) )
			$this->errors[ $code ] = array();

		if ( ! empty( $data ) )
			$this->errors[ $code ] = $data;
		elseif ( ! empty( $this->product_id ) )
			$this->errors[ $code ][] = $this->product_id;
	}

	/**
	 * Fetch the product rent interval
	 * @since    0.1
	 * @access   protected
	 * @param    void
	 * @return   array
	 */
	protected function get_rent_interval() {
		global $bws_bkng;
		
		$timestamp     = current_time( 'timestamp' );
		$next_day      = $timestamp + ( HOUR_IN_SECONDS - $timestamp % HOUR_IN_SECONDS ) + DAY_IN_SECONDS;
		$interval_slug = $bws_bkng->get_product_rent_interval( $this->product_id );
		$step          = $bws_bkng->get_rent_interval( $interval_slug, 'number' );

		if ( empty( $this->raw['rent_interval']['from'] ) || empty( $this->raw['rent_interval']['till'] ) ) {
			return array(
				'from' => $next_day,
				'till' => $next_day + $step,
				'step' => $step
			);
		} else {

			$raw_from = absint( $this->raw['rent_interval']['from'] );
			$raw_till = absint( $this->raw['rent_interval']['till'] );
			$from     = empty( $raw_from ) ? $next_day : $raw_from;
			$till     = empty( $raw_till ) ? $next_day + $step : $raw_till;

			if ( $from == $till )
				$till += $step;

			return array(
				'from' => $from < $till ? $from : $till,
				'till' => $from < $till ? $till : $from,
				'step' => $step
			);
		}
	}

	/**
	 * Fetch the product rent interval
	 * @since    0.1
	 * @access   protected
	 * @param    void
	 * @return   int
	 */
	protected function get_quantity() {

		if ( empty( $this->raw['quantity'] ) ) {
			return 1;
		} else {
			$quantity = absint( $this->raw['quantity'] );

			if ( empty( $quantity ) )
				return 1;

//			$max_quantity = get_post_meta( $this->product_id, 'bkng_in_stock', true );
//			todo get $max_quantity
			$max_quantity = 100;
			return $quantity <= $max_quantity ? $quantity : $max_quantity;
		}
	}

	/**
	 * Checks whether there are any products with "price_on_request" enabled option
	 * @since    0.1
	 * @access   protected
	 * @param    array       $ids    The products' IDs list
	 * @return   boolean
	 */
	protected function show_summaries( $ids ) {
		global $wpdb;

		$ids = implode( ',', (array)$ids );

		return ! $wpdb->get_var(
		    $wpdb->prepare(
                "SELECT `meta_value` FROM `{$wpdb->postmeta}` WHERE `meta_key` = 'bkng_price_on_request' AND `meta_value` = '1' AND `post_id` IN (%s) LIMIT 1;",
                $ids
            )
        );
	}

	/**
	 * Fetch the product subtotal according to its price, rent interval or quantity
	 * @since    0.1
	 * @access   protected
	 * @param    array       $args    The product data
	 * @return   float                The product subtotal
	 */
	protected function get_product_subtotal( $args ) {

		extract( $args );

		$product_id = empty( $id ) ? $this->product_id : $id;
		$subtotal   = empty( $rent_interval['step'] ) ? $price : ceil( ( $rent_interval['till'] - $rent_interval['from'] ) / $rent_interval['step'] ) * $price;
		$subtotal  *= $quantity;

		/**
		 * @uses  To change the product subtotal according to your own formula (eg. accordng to the rent interval, amount of product etc.)
		 *        !!!! Don't recomended to change the product subtotal including taxes or sales - use "bws_bkng_product_total" instead
		 * @param   int      $subtotal    The product subtotal
		 * @param   array    $args        The list of parmeters are used to generate the subtotal
		 * @param   object   $product     WP_Post object
		 * @return  float    $subtotal    The product subtotal
		 */
		return round( apply_filters( 'bws_bkng_product_subtotal', $subtotal, $args, $product_id ), 2 );
	}

	/**
	 * Fetch the product total according to the special offers, sales etc.
	 * @since    0.1
	 * @access   protected
	 * @param    array       $args    The product data
	 * @return   float                The product total
	 */
	protected function get_product_total( $subtotal ) {
		/**
		 * @uses    To change the product subtotal including taxes or sales
		 * @param   float    $subtotal    The product subtotal
		 * @param   int                   The product ID
		 * @return  float                 The product total
		 */
		return round( apply_filters( 'bws_bkng_product_total', $subtotal, $this->product_id ), 2 );
	}

	/**
	 * Fetch the order total
	 * @since    0.1
	 * @access   protected
	 * @param    array       $products    The products data
	 * @param    boolean     $return      Whether to return the results
	 * @return   array|void
	 */
	protected function count_order_total( $products = false, $return = false ) {
		$subtotal = $extras_total = $total = 0;

		if ( empty( $products ) )
			$products = $this->content['products'];

		foreach ( $products as $product ) {

			if ( ! empty( $product['linked_to'] ) )
				$extras_total += $product['total'];

			$subtotal += $product['total'];
		}

		/**
		 * @uses    To change the subtotal cost of order subtotal according to your own formula
		 *          (eg. accordng to the rent interval, amount of products etc.).
		 *          !!! Don't use it to include taxes or sales which are applied to the whole order - use "bws_bkng_order_total" instead
		 * @param   float      $subtotal    Products subtotal
		 * @param   array      $products    The list of products
		 * @return  float                   Products subtotal
		 */
		$subtotal = round( apply_filters( 'bws_bkng_order_subtotal', $subtotal, $products ), 2 );

		/**
		 * Is used to change the total cost of order including taxes, sales, etc
		 * @param   float      $subtotal   Products subtotal
		 * @param   array     $products    The list of products
		 * @return  float                  Products total
		 */
		$total = round( apply_filters( 'bws_bkng_order_total', $subtotal, $products ), 2 );

		if ( $return )
			return compact( 'extras_total', 'subtotal', 'total' );

		$this->content['extras_total'] = $extras_total;
		$this->content['subtotal']     = $subtotal;
		$this->content['total']        = $total;
	}

	/**
	 * Sort the list of orderd products before displaying them
	 * in order to show them to the user in the format:
	 *   {main_product_1}
	 *   {main_product_1_extra_1}
	 *   {main_product_1_extra_2}
	 *   ...
	 *   {main_product_1_extra_N}
	 *   {main_product_2}
	 *   {main_product_2_extra_1}
	 *   {main_product_2_extra_2}
	 *   ...
	 *   {main_product_2_extra_N}
	 *   ...
	 * @since    0.1
	 * @access   protected
	 * @param    void
	 * @return   void
	 */
	protected function sort_products() {

		if ( empty( $this->content['products'] ) )
			return;

		$temp = $linked = $content = array();

		foreach( $this->content['products'] as $id => $product ) {
			if ( empty( $product['linked_to'] ) ) {
				$temp[ $id ] = $product;
				continue;
			}

			if ( empty( $linked[ $product['linked_to'] ] ) )
				$linked[ $product['linked_to'] ] = array();

			$linked[ $product['linked_to'] ][ $id ] = $product;
		}

		if ( empty( $temp ) )
			return;

		foreach ( $temp as $id => $product ) {
			$content[ $id ] = $product;

			if ( ! empty( $linked[ $id ] ) )
				$content += $linked[ $id ];
		}

		$this->content['products'] = $content;
	}

	/**
	 * Prepares the each product's data before saving
	 * @since    0.1
	 * @abstract
	 * @access   protected
	 * @param    void
	 * @return   array    The product data
	 */
	abstract protected function prepare();

	/**
	 * Prepares the each product's data before displaying
	 * @since    0.1
	 * @abstract
	 * @access   protected
	 * @param    void
	 * @return   array    The products data list
	 */
	abstract protected function prepare_to_display();

	/**
	 * Fetch the list of table columns for the following displaying
	 * @since    0.1
	 * @abstract
	 * @access   protected
	 * @param    void
	 * @return   array   The list of colums in format {column_slug} => {column_title}
	 */
	abstract protected function get_columns();

}