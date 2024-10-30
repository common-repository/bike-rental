<?php
/**
 * Froms the data of the products pre-order list.
 * The class is a child of BWS_BKNG_Order_Handler  in order to fetch the same data
 * during using filters for ordered products total and subtotal
 *
 * @uses     in the BWS Booking's "pre-order" template
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Pre_Order' ) )
	return;


class BWS_BKNG_Pre_Order extends BWS_BKNG_Order_Handler {

	/**
	 * Contains the main ordered product data
	 * @since  0.1
	 * @access private
	 * @var    array
	 */
	private $product_data;

	/**
	 * Contains an instance of the current class
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    object
	 */
	private static $instance;
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

	public function get_subtotal() {
		return $this->get_product_total( $this->get_product_subtotal( $this->product_data ) );
	}

	/**
	 * Class constructor.
	 * Form the current product data to display them in the pre-order block {@see template pre-order.php}
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   void
	 */
	private function __construct( $post = null ) {
		global $bws_bkng;

		$post = get_post( $post );

		if ( empty( $post->ID ) )
			return;

		$id                    = $post->ID;
		$price                 = bws_bkng_get_product_price();
		$rent_interval         = bws_bkng_get_session_rent_interval();
		$interval_slug         = $bws_bkng->get_product_rent_interval( $this->product_id );
		$rent_interval['step'] = $bws_bkng->get_rent_interval( $interval_slug, 'number' );
		$quantity              = 1;

		$this->product_data = compact( 'id', 'price', 'rent_interval', 'quantity' );
	}

	/**
	 * Implementing of abstract methods of the parent class.
	 * In a fact they don't needed for class functionality thet is why
	 * there are just empty bodies
	 */
	protected function prepare()            {}
	protected function prepare_to_display() {}
	protected function get_columns()        {}
	public    function get_errors()         {}

	/**
	 * Some magic to avoid the creation of several instances of this class
	 * @since  0.1
	 */
	private function __clone()     {}
	private function __sleep()     {}
	private function __wakeup()    {}

}