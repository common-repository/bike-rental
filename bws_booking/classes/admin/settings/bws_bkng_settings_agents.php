<?php
/**
 * Handle the content of "Agents" tab
 * @since  Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Settings_Agents' ) )
	return;

class BWS_BKNG_Settings_Agents extends BWS_BKNG_Settings_Tabs {

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
		$this->options['agents_page'] = absint( $_POST['bkng_agents_page'] );
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
		<table class="form-table">
			<?php /**
			 * Agents page select
			 */
			$content = $bws_bkng->get_list_pages( 'agents_page' );
			$bws_bkng->display_table_row( __( 'Agents page', BWS_BKNG_TEXT_DOMAIN ), $content ); ?>
		</table>
	<?php }


}