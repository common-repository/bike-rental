<?php
/**
 * Handle the content of "Users" tab
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Settings_Users' ) )
	return;

class BWS_BKNG_Settings_Users extends BWS_BKNG_Settings_Tabs {

	/**
	 * Class constructor
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function __construct() {
		$this->tabs = array(
			'display' => array( 'label' => __( 'Display', BWS_BKNG_TEXT_DOMAIN ) )
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
	public function prepare_options() {
		/* booleans */
		$this->options['account_registration'] = ! empty( $_POST['bkng_account_registration'] );

		/* numbers */
		$this->options['user_account_page'] = absint( $_POST['bkng_user_account_page'] );

		/* strings */
		foreach( $_POST['bkng_account_endpoints'] as $endpoint => $value ) {
			$value = sanitize_text_field( stripslashes( $value ) );
            $endpoint = sanitize_text_field( stripslashes( $endpoint ) );
			if ( ! empty( $value ) )
				$this->options["account_endpoints"][ $endpoint ] = $value;
		}
	}

	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function tab_display() {
		global $bws_bkng; ?>
		<h3 class="bws_tab_label"><?php _e( 'Display Settings', BWS_BKNG_TEXT_DOMAIN ); ?></h3>
		<table class="form-table">
			<?php /**
			 * User Account Page select
			 */
			$content = $bws_bkng->get_list_pages( 'user_account_page' );
			$bws_bkng->display_table_row( __( 'User Account Page', BWS_BKNG_TEXT_DOMAIN ), $content );

			/**
			 * Enable registration option
			*/
			$name  = 'bkng_account_registration';
			$attr  = $this->options["account_registration"] ? 'checked="checked"' : '';

			$content = $bws_bkng->get_checkbox( compact( 'name', 'attr' ) );

			$bws_bkng->display_table_row( __( 'Enable registration', BWS_BKNG_TEXT_DOMAIN ), $content );
			?>
		</table>
		<h4 class="bkng_tab_sub_title"><?php echo __( 'Account Endpoints', BWS_BKNG_TEXT_DOMAIN ) . $bws_bkng->get_tooltip( __( "Postfixes to links used on user accounts pages to perform certain actions. They must be unique and not contain spaces or other characters that are not allowed for use in the links.", BWS_BKNG_TEXT_DOMAIN ) . '.' ); ?></h4>
		<table class="form-table">
			<?php /**
			 * Account endpoints options
			 */
			$endpoints = $bws_bkng->get_endpoints();
			$option    = $this->options["account_endpoints"];

			foreach( $endpoints as $endpoint => $data ) {
				$name  = "bkng_account_endpoints[{$endpoint}]";
				$value = $option[ $endpoint ];

				$content = $bws_bkng->get_text_input( compact( 'name', 'value' ) ) . ( empty( $data[1] ) ? '' : $bws_bkng->get_tooltip( $data[1] ) );

				$bws_bkng->display_table_row( $data[0], $content );
			} ?>
		</table>
	<?php }
}