<?php
/**
 * Handle the content of "Cart" tab
 * @since  Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Settings_Cart' ) )
	return;

class BWS_BKNG_Settings_Cart extends BWS_BKNG_Settings_Tabs {

	/**
	 * Class constructor
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function __construct() {
		$this->tabs = array(
			'general' => array( 'label' => __( 'General', BWS_BKNG_TEXT_DOMAIN ) )
		);

		parent::__construct();
	}

	/**
	 * Prepares the plugin options before further saving to database
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function prepare_options( $options = NULL ) {
		global $bws_bkng;

		if ( NULL !== $options ) {
			$this->options = $options;
		}

		/* numbers */
		$this->options['cart_page']          = absint( $_POST['bkng_cart_page'] );
		$this->options['keep_goods_in_cart'] = absint( $_POST['bkng_keep_goods_in_cart'] );

		if ( NULL !== $options ) {
			return $this->options;
		}
	}

	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function tab_general() {
		global $bws_bkng; ?>
		<table class="form-table">
			<?php /**
			 * Cart page select
			 */
			$content = $bws_bkng->get_list_pages( 'cart_page' );
			$bws_bkng->display_table_row( __( 'Cart page', BWS_BKNG_TEXT_DOMAIN ), $content );
			/**
			 * Cart lifetime option
			 */
			$name    = 'bkng_keep_goods_in_cart';
			$min     = 1;
			$value   = $this->options['keep_goods_in_cart'];
			$after   = __( 'days', BWS_BKNG_TEXT_DOMAIN );
			$content = $bws_bkng->get_number_input( compact( 'after', 'name', 'value', 'min' ) );
			$bws_info_text = __( "The user's cart will be automatically erased after the end of goods shelf life in it", BWS_BKNG_TEXT_DOMAIN );

			$bws_bkng->display_table_row( __( 'Keep goods in the cart', BWS_BKNG_TEXT_DOMAIN ), $content . '<div class="bws_info">' . $bws_info_text . '</div>' ); ?>
		</table>
	<?php }

	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function display_tab_content() {
		global $bws_bkng;
        $this->tab_title( __( 'Cart', BWS_BKNG_TEXT_DOMAIN ) ); ?>
		<table class="form-table">
			<?php /**
			 * Cart page select
			 */
			$content = $bws_bkng->get_list_pages( 'cart_page' );
			$bws_bkng->display_table_row( __( 'Cart page', BWS_BKNG_TEXT_DOMAIN ), $content );
			/**
			 * Cart lifetime option
			 */
			$name    = 'bkng_keep_goods_in_cart';
			$min     = 1;
			$value   = $this->options['keep_goods_in_cart'];
			$after   = __( 'days', BWS_BKNG_TEXT_DOMAIN );
			$content = $bws_bkng->get_number_input( compact( 'after', 'name', 'value', 'min' ) );
			$bws_info_text = __( "The user's cart will be automatically erased after the end of goods shelf life in it", BWS_BKNG_TEXT_DOMAIN );

			$bws_bkng->display_table_row( __( 'Keep goods in the cart', BWS_BKNG_TEXT_DOMAIN ), $content . '<div class="bws_info">' . $bws_info_text . '</div>' ); ?>
		</table>
	<?php }


}
