<?php
/*
Plugin Name: Bike Rental by BestWebSoft
Plugin URI: https://bestwebsoft.com/products/wordpress/plugins/bike-rental/
Description: Give a birth for your bike rental and booking WordPress website.
Author: BestWebSoft
Text Domain: bike-rental
Domain Path: /languages
Version: 1.0.2
Author URI: https://bestwebsoft.com/
License: GPLv3 or later
*/

/*  © Copyright 2021 BestWebSoft  ( https://support.bestwebsoft.com )

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) )
	define( 'ABSPATH', dirname(__FILE__) . '/' );

if ( ! defined( 'ABSPATH' ) )
	die();

/**
 * Inits the main plugin functionality
 * @since  1.0.0
 */
if ( ! class_exists( 'BWS_BKRNTL' ) ) {

	class BWS_BKRNTL {

		/**
		 * Contains an instance of the current class
		 * @since  1.0.0
		 * @access private
		 * @static
		 * @var    object
		 */
		private static $instance;

		/**
		 * Fetch the class instance
		 * @since    1.0.0
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
         * The plugin initialization
         * @return   void
         * @since    1.0.0
         * @access   public
         */
		public function init_booking() {
			BWS_BKNG::init( array(
				'plugin_file'                   => __FILE__,
				'plugin_prefix'                 => 'bkrntl',
				'wp_slug'                       => 'bike-rental',
				'settings_page_slug'            => 'bkng_general_settings',
				'settings_page_title'           => __( 'Bike Rental Settings', 'bike-rental' ),
				'general_settings_page_slug'    => 'bkng_general_settings',
				'general_settings_page_title'   => __( 'Bike Rental General Settings', 'bike-rental' ),
				'link_key'                      => '664b00b8cd82b35c4f9b2a4838de35ff',
				'link_pn'                       => '965',
				'allow_variations'              => false, /* in the Free version this can be omitted */
				'is_pro'                        => false,
				'plugins_link_keys'             => array(
					'captcha'   => '8281e6d44a90befd427597e740c32313',
					'recaptcha' => 'ed8318cb8dd1ab61cdddbc8c84e9311b'
				),
				'plugin_upgrade_versions'		=> array( /* specify available free\pro\plus versions for the current view — needed when deactivating */
					'free' => 'bike-rental/bike-rental.php'
				),
				'require_files'                 => array(
					'/booking_adapter/class_bkrntl_booking_adapter.php',
					'/booking_adapter/class_bkrntl_locations.php',
					'/booking_adapter/class_bkrntl_bike_widget.php',
					'/booking_adapter/plugin_settings.php',
				)
			) );
		}

		/**
		 * Inits plugin textdomain after all plugins were completely loaded
		 * @since    1.0.0
		 * @access   public
		 * @param    void
		 * @return   void
		 */
		public function plugins_loaded() {
			load_plugin_textdomain( 'bike-rental', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Adds the plugin scripts to the site front end
		 * @since    1.0.0
		 * @access   public
		 * @param    void
		 * @return   void
		 */
		public function enqueue_scripts() {
			global $bws_bkng;

			if ( $bws_bkng->is_admin )
				return;

			$info = $bws_bkng->get_plugin_info();
			$url  = plugins_url( '/', __FILE__ );
			wp_enqueue_style( 'bkrntl-front-end-styles', "{$url}css/front-end-styles.css", array(), $info['Version'] );
			wp_enqueue_script( 'bkrntl-front-end-script', "{$url}js/script.js", array( 'jquery', 'bkng_front_script' ), $info['Version'] );
			$data = array(
				'empty_return_location' => __( 'Please select Drop-off Location', 'bike-rental' ),
				'empty_pickup_location' => __( 'Please select Pick-up location', 'bike-rental' )
			);
			wp_localize_script( 'bkrntl-front-end-script', 'bkrntl', $data );

		}

		/**
		 * Adds the plugin scripts to the site admin panel
		 * @since    1.0.0
		 * @access   public
		 * @param    void
		 * @return   void
		 */
		public function admin_enqueue_scripts() {
			global $bws_bkng;
			$info = $bws_bkng->get_plugin_info();
			wp_enqueue_style( 'bkrntl-admin-general-stylesheet', plugins_url( 'css/admin-general-styles.css', __FILE__ ), array(), $info['Version'] );
			wp_enqueue_style( 'bws-modal-css', bws_menu_url( 'css/modal.css' ) );
		}

		/**
		 * Displays notices in Admin panel
		 * @since    1.0.0
		 * @access   public
		 * @param    void
		 * @return   void
		 */
		public function admin_notices() {
			global $bws_bkng;
			$theme = wp_get_theme();

			if ( isset( $_REQUEST['bkrntl_hide_theme_banner'] ) )
				$bws_bkng->update_option( true, 'hide_theme_banner' );

			if ( 'Rent a Bike' == $theme->get( 'Name' ) || $bws_bkng->get_option( 'hide_theme_banner' ) )
				return;

			$info = $bws_bkng->get_plugin_info(); ?>

			<div class="updated" style="padding: 0; margin: 0; border: none; background: none;position: relative;">
				<div class="notice notice-info bkrntl-unsupported-theme-notice">
					<p>
						<strong><?php printf(
							__( 'Your theme does not declare %1$s plugin support. Please check out our %2$s theme which has been developed specifically for use with %1$s plugin.', 'bike-rental' ),
							$info['Name'],
							'<a href="https://bestwebsoft.com/products/wordpress/themes/rent-a-bike-booking-wordpress-theme/?k=46b9216a246710037171f77eff6ed0bb" target="_blank">Rent a Bike</a>' );
						?></strong>
					</p>
				</div>
				<?php $url = add_query_arg( 'bkrntl_hide_theme_banner', 1, $bws_bkng->get_current_url() ); ?>
				<a class="notice-dismiss bws_hide_settings_notice" href="<?php echo esc_url( $url ); ?>" style="top: 5px;text-decoration: none;"></a>
			</div>
		<?php }

		/**
		 * Adds additional links to the plugin description column on the plugins list page in admin panel
		 * @since    1.0.0
		 * @access   public
		 * @param    array     $links
		 * @param    string    $file
		 * @return   array
		 */
		public function plugin_row_meta( $links, $file ) {
			global $bws_bkng;

			if ( plugin_basename( __FILE__ ) != $file )
				return $links;

			if ( ! is_network_admin() )
				$links[] = '<a href="' . esc_url( admin_url( "edit.php?post_type=bws_bike&page={$bws_bkng->settings_page_slug}" ) ) . '">' . __( 'Settings', 'bike-rental' ) . '</a>';

			$links[] = '<a href="https://support.bestwebsoft.com/hc/en-us/sections/360010494692" target="_blank">' . __( 'FAQ', 'bike-rental' ) . '</a>';
			$links[] = '<a href="https://support.bestwebsoft.com">' . __( 'Support', 'bike-rental' ) . '</a>';

			return $links;
		}

		/**
		 * Adds "Settings" link to the plugin name column on the plugins list page in admin panel
		 * @since    1.0.0
		 * @access   public
		 * @param    array     $links
		 * @param    string    $file
		 * @return   array
		 */
		public function plugin_action_links( $links, $file ) {
			global $bws_bkng;

			if ( is_network_admin() || plugin_basename( __FILE__ ) != $file )
				return $links;

			$links[] = '<a href="' . esc_url( admin_url( "edit.php?post_type=bws_bike&page={$bws_bkng->settings_page_slug}" ) ) . '">' . __( 'Settings', 'bike-rental' ) . '</a>';

			return $links;
		}



		/**
		 * The class constructor
		 * @since    1.0.0
		 * @access   private
		 * @param    void
		 * @return   void
		 */
		private function __construct() {
			add_action( 'after_setup_theme', array( $this, 'init_booking' ) );
			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
			require_once( dirname( __FILE__ ) . '/bws_booking/autoload.php' );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
			add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
		}

		/**
		 * Some magic to avoid the creation of several instances of this class
		 * @since  1.0.0
		 */
		private function __clone()  {}
		private function __sleep()  {}
		private function __wakeup() {}
	}
}
BWS_BKRNTL::get_instance();
