<?php
/**
 * Run scheduled callbacks for all plugin events.
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Cron' ) )
	return;

class BWS_BKNG_Cron {

	/**
	 * Class constructor
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function __construct() {
		global $bws_bkng;

		if ( $bws_bkng->is_pro ) {
			add_action(
				str_replace( '-', '_', pathinfo( $bws_bkng->plugin_file, PATHINFO_FILENAME ) ) . '_license_cron',
				array( $this, 'license_cron_task' )
			);
		}

		add_action( 'bws_bkng_clear_cart', array( $this, 'clear_cart' ) );
	}

	/**
	 * Checks the license for the pro version of the plugin
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function license_cron_task() {
		global $bws_bkng;
		if ( ! function_exists( 'bestwebsoft_license_cron_task' ) )
			require_once( plugin_dir_path( $bws_bkng->plugin_file ) . 'bws_update.php' );

		bestwebsoft_license_cron_task(
			$bws_bkng->plugin_basename,
			str_replace( '-pro', '', $bws_bkng->plugin_basename )
		);
	}

	/**
	 * Remove old data from the cart database storage
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function clear_cart() {
		global $wpdb, $bws_bkng;
		$table = BWS_BKNG_DB_PREFIX . 'session';
		$now   = current_time( 'timestamp' );
		$data  = $wpdb->get_var( "SELECT `id` FROM `{$table}` LIMIT 1;" );
		if ( 0 === $wpdb->query( $wpdb->prepare( "DELETE FROM `{$table}` WHERE `expires`<=%s;", $now ) ) && empty( $data ) )
			wp_clear_scheduled_hook( 'bws_bkng_clear_cart' );
	}
}