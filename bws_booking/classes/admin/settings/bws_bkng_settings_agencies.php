<?php
/**
 * Handle the content of "Agencies" tab
 * @since  Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Settings_Agencies' ) )
	return;

class BWS_BKNG_Settings_Agencies extends BWS_BKNG_Settings_Tabs {

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
		global $bws_bkng;
		/* booleans */
		$this->options['enable_agencies'] = ! empty( $_POST['bkng_enable_agencies'] );

		$meta = $bws_bkng->get_agencies_meta_fields( true );
		foreach( $meta as $field )
			$this->options['agencies_additional_meta'][ $field ] = ! empty( $_POST['bkng_agencies_additional_meta'][ $field ] );

		/* numbers */
		$this->options['agencies_page'] = absint( $_POST['bkng_agencies_page'] );
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
			 * Enable Agencies option
			 */
			$name  = 'bkng_enable_agencies';
			$attr  = isset( $this->options['enable_agencies'] ) && $this->options['enable_agencies'] ? 'checked="checked"' : '';

			$content = $bws_bkng->get_checkbox( compact( 'name', 'attr' ) );

			$bws_bkng->display_table_row( __( 'Enable', BWS_BKNG_TEXT_DOMAIN ), $content );

			/**
			 * Agencies page select
			 */
			$content = $bws_bkng->get_list_pages( 'agencies_page' );
			$bws_bkng->display_table_row( __( 'Agencies Page', BWS_BKNG_TEXT_DOMAIN ), $content );

			/*
			 * Additional metadata
			 */
			$meta    = $bws_bkng->get_agencies_meta_fields();
			$option  = isset( $this->options['agencies_additional_meta'] ) ? $this->options['agencies_additional_meta'] : '';
			$content = '';
			foreach ( $meta as $field => $label ) {
				$name     = "bkng_agencies_additional_meta[{$field}]";
				$attr     = isset( $option[ $field ] ) && $option[ $field ] ? 'checked="checked"' : '';
				$after    = $label;
				$checkbox = $bws_bkng->get_checkbox( compact( 'name', 'attr', 'after' ) );
				$content .= "<p>{$checkbox}</p>";
			}
			$bws_bkng->display_table_row( __( 'Additional Fields', BWS_BKNG_TEXT_DOMAIN ), $content ); ?>
		</table>
	<?php }
}