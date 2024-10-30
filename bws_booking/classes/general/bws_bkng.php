<?php
/**
 * Inits Booking's core functionality
 * @version  0.2
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG' ) )
	return;

class BWS_BKNG {

	/**
	 * Contains an instance of the Data Loader Class
	 * @since  0.1
	 * @access public
	 * @var object
	 */
	public $data_loader;

	/**
	 * Contains the list of initialized helpers
	 * @since  0.1
	 * @access public
	 * @var array of objects
	 */
	public $helpers = array();

	/**
	 * Contains the name of CSS-class for hidden HTML-elelments
	 * @since  0.1
	 * @access public
	 * @static
	 * @var string
	 */
	public static $hidden = 'bkng_hidden';

	/**
	 * Contains the name of CSS-class for hidden HTML-elelments
	 * which need to be showed if JS is disabled
	 * @since  0.1
	 * @access public
	 * @static
	 * @var string
	 */
	public static $show_if_no_js = 'bkng_show_if_no_js';

	/**
	 * Contains the name of CSS-class for HTML-elelments
	 * which need to be hidden if JS is disabled
	 * @since  0.1
	 * @access public
	 * @static
	 * @var string
	 */
	public static $hide_if_no_js = 'bkng_hide_if_no_js';

	/**
	 * Contains an instance of the current class
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    object
	 */
	private static $instance;

	/**
	 * Conainer for the additional plugin data
	 * @since  0.1
	 * @access private
	 * @var    array
	 */
	private $data = array();

	private static $version = 0.1;

	private static $db_version = 0.2;

	/**
	 * Fetch the class instance
	 * @since    0.1
	 * @access   public
	 * @static
	 * @param    void
	 * @return   object    An instance of the current class
	 */
	public static function get_instance( $args ) {

		if ( ! self::$instance instanceof self )
			self::$instance = new self( $args );

		return self::$instance;
	}

	public static function get_version() {
		return self::$version;
	}

	public static function get_db_version() {
		return self::$db_version;
	}

	/**
	 * Fires the booking core initialization
	 * @since    0.1
	 * @access   public
	 * @static
	 * @param    void
	 * @return   object    An instance of the current class
	 */
	public static function init( $args ) {
		$GLOBALS['bws_bkng'] = self::get_instance( $args );
		/**
		 * Rename options - for old plugin version
		 * @deprecated 1.0.8
		 * @todo Remove function after 01.05.2019
		 */
		$default_options = $GLOBALS['bws_bkng']->data_loader->load( 'default_settings' );
		$options = get_option( BWS_BKNG_PURE_SLUG . '_options' );
		$options = array_merge( $default_options, $options );
		if ( 'before' == $options['currency_position'] ) {
			$options['currency_position'] = 'left';
		}
		if ( 'after' == $options['currency_position'] ) {
			$options['currency_position'] = 'right';
		}
		update_option( BWS_BKNG_PURE_SLUG . '_options', $options );
		/**
		 * End deprecated
		 */
	}

	/**
	 * Class constructor
	 * @since  0.1
	 * @access private
	 * @param  string  $args
	 * @return void
	 */
	private function __construct( $args ) {
		global $wpdb;

		$args = wp_parse_args( $args, array(
			'plugin_file'         => '',
			'settings_page_slug'  => basename( __FILE__ ),
			'settings_page_title' => 'Booking Settings',
			'plugin_prefix'       => '',
			'wp_slug'             => '',
			'bws_license_plugin'  => '',
			'link_key'            => '',
			'link_pn'             => '',
			'require_files'		=> array()
		) );

		$this->data = array_merge( $this->data, $args );

		if ( empty( $this->plugin_file ) )
			wp_die( 'The "plugin_file" parameter must not be empty' );

		$this->data['plugin_basename'] = plugin_basename( $this->plugin_file );

		$dirname = dirname( dirname( dirname( __FILE__ ) ) );
		$pure_slug = preg_replace( '#[-_aeiou]+#i', '', $this->data['wp_slug'] );
		$constants = array(
			'BWS_BKNG_PATH'          => "{$dirname}/",
			'BWS_BKNG_FOLDER'        => basename( $dirname ),
			'BWS_BKNG_FILE'          => basename( __FILE__ ),
			'BWS_BKNG_URL'           => plugins_url( "/" . basename( $dirname ) . "/", $dirname ),
			'BWS_BKNG_TEXT_DOMAIN'   => 'bws_booking',
			'BWS_BKNG_POST'          => 'bws_bkng_products',
			'BWS_BKNG_CATEGORIES'    => 'bws_bkng_categories',
			'BWS_BKNG_AGENCIES'      => 'bws_bkng_agencies',
			'BWS_BKNG_TAGS'          => 'bws_bkng_tags',
			'BWS_BKNG_SLUG'			 =>  $this->data['wp_slug'],
			'BWS_BKNG_PURE_SLUG'	 =>	 $pure_slug,
			'BWS_BKNG_DB_PREFIX'     => "{$wpdb->prefix}bws_bkng_"
		);

		if ( $this->allow_variations )
			$constants['BWS_BKNG_VARIATION'] = 'bkng_variations';

		foreach ( $constants as $const => $value ) {
			if ( ! defined( $const ) )
				define( $const, $value );
		}

		if ( ! function_exists( 'get_plugin_data' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		foreach ( $this->data['require_files'] as $file ) {
			require_once( dirname( $this->data['plugin_file'] ) . $file );
		}

		$this->init_helpers();
		$this->init_functions();
		$this->data_loader = new BWS_BKNG_Data_Loader();

		register_activation_hook( $this->plugin_file, array( $this, 'activate' ) );
		add_action( 'after_switch_theme', array( $this, 'activate' ) );
		register_uninstall_hook( $this->plugin_file, array( 'BWS_BKNG_Uninstaller', 'uninstall' ) );

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'widgets_init', array( $this, 'init_widgets' ) );
		add_action( 'init', array( $this, 'load_inits' ) );
		add_filter( 'get_avatar_url', array( $this, 'get_user_avatar_url' ), 99, 3 );
		add_filter( 'cron_schedules', array( $this, 'add_cron_shedules' ) );
		add_filter( 'default_option_' . $this->plugin_prefix . '_options', array( $this->data_loader, 'add_default_option' ) );
		add_filter( 'option_' . $this->plugin_prefix . '_options', array( $this->data_loader, 'upgrade_option' ) );
		add_action( 'delete_blog', array( $this, 'delete_blog' ) );

		if ( $this->is_pro ) {
			add_action( 'after_plugin_row_' . $this->plugin_basename, array( $this, 'plugin_update_row' ), 10, 2 );
			add_filter( 'plugins_api', array( $this, 'inject_info' ), 20, 3 );
		}
	}

	/**
	 * Fires during the plugin activation
	 * @see
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function activate() {
		global $wpdb;
		if ( is_multisite() ) {
			$old_blog = $wpdb->blogid;
			$blogids  = $wpdb->get_col( "SELECT `blog_id` FROM `{$wpdb->blogs}`;" );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				$this->data_loader->init_roles();
			}
			switch_to_blog( $old_blog );
		} else {
			$this->data_loader->init_roles();
		}
	}

	/**
	 * Loads plugin text domain
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( BWS_BKNG_FOLDER, false, plugin_basename( BWS_BKNG_PATH ) . '/languages/' );
	}

	public function init_widgets() {
		$widgets = array( 'BWS_BKNG_Pre_Order_Widget', 'BWS_BKNG_Search_Filter_Widget' );
		foreach ( $widgets as $widget )
			register_widget( $widget );
	}

	/**
	 * Initializes necessary helper classes
	 * @see
	 * @since  0.1
	 * @access public
	 * @param  string
	 * @return void
	 */
	public function add_helper( $class ) {
		$this->helpers[ $class ] = new $class();
	}

	/**
	 * Initializes the main plugin functionality
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function load_inits() {

		$dirname  = dirname( $this->plugin_file );

		require_once( "{$dirname}/bws_menu/bws_include.php" );
		bws_include_init( $this->plugin_basename );

		bws_wp_min_version_check( $this->plugin_basename, $this->get_plugin_info(), '4.5' );

		if ( $this->is_pro ) {
			$this->update_activate();
		}

		//$this->data_loader->register_booking_objects();

		BWS_BKNG_Related_Plugins::init_hooks();

		$this->set_image_sizes();

		if ( $this->is_admin() )
			new BWS_BKNG_Admin();
		elseif ( defined( 'DOING_AJAX' ) )
			new BWS_BKNG_AJAX();
		else
			new BWS_BKNG_Front();

		new BWS_BKNG_Cron();
	}

	public function get_plugin_info() {
		if ( ! function_exists( 'get_plugin_data' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$info = get_plugin_data( $this->plugin_file );

		return $info;
	}

	/**
	 * Changes Wordpress's avatar url to the one set in user profile settings
	 * @since  0.1
	 * @access public
	 * @param  string     $url    Wordpress's native url for avatar
	 * @return string     $avatar_url
	 */
	public function get_user_avatar_url( $url, $id_or_email, $args ) {
		if ( isset( $args['bkng'] ) || empty( $id_or_email ) ) {
			return $url;
		}

		$user = BWS_BKNG_User::get_instance();

		if ( is_numeric( $id_or_email ) ) {
			$user->user_id = $id_or_email;
		} elseif ( is_object( $id_or_email ) ) {
			if ( $id_or_email->user_id != '0' ) {
				$user->user_id = $id_or_email->user_id;
			} else {
				return $url;
			}
		} else {
			$user_object = get_user_by( 'email', $id_or_email );
			$user->user_id = $user_object->ID;
		}

		$avatar_url = $user->get_avatar_url();

		if ( ! $avatar_url ) {
			return $url;
		}

		return $avatar_url;
	}

	/**
	 * Adds necessary time intervals to run plugin cron jobs
	 * @since  0.1
	 * @access public
	 * @param  array      $schedules    The list of intervals when the cron jobs should run.
	 * @return array      $schedules
	 */
	public function add_cron_shedules( $shedules ) {
		$schedules['bws_bkng_keep_goods_in_cart'] = array(
			'interval' => absint( $this->get_option( 'keep_goods_in_cart' ) ) * DAY_IN_SECONDS,
			'display'  => __( 'The period of keeping products in the cart', BWS_BKNG_TEXT_DOMAIN )
		);
		return $schedules;
	}

	/**
	 * Fetch the plugin options
	 * @since  0.1
	 * @access public
	 * @param  string
	 * @return mixed
	 */
	public function get_option( $field = '' ) {
		if ( 'attributes' == $field ) {
			if ( empty( $this->data['attributes'] ) )
				$this->data['attributes'] = array_filter( (array)get_option( "bkng_attributes" ) );
			return $this->data['attributes'];
		}

		if ( isset( $_GET['post_type'] ) && isset( $_GET['page'] ) && $this->plugin_prefix . '_' . $_GET['post_type'] . '_settings' == $_GET['page'] ) {
		    $value = $this->get_post_type_option( sanitize_text_field( stripslashes( $_GET['post_type'] ) ), $field );
			return $value;
		} else {
			if ( empty( $this->data['options'] ) )
				$this->data['options'] = get_option( "{$this->plugin_prefix}_options" );

			if ( ! is_string( $field ) )
				return null;

			$field = strval( $field );

			if ( empty( $field ) )
				return $this->data['options'];

			return isset( $this->data['options'][ $field ] ) ? $this->data['options'][ $field ] : null;
		}
	}

	/**
	 * Fetch the plugin options
	 * @since  0.1
	 * @access public
	 * @param  string
	 * @return mixed
	 */
	public function get_post_type_option( $post_type, $field = '' ) {
	    if ( is_array( $post_type ) ) {
			$post_type = $post_type[0];
		}
        if ( ! is_string( $post_type ) ) {
            return null;
        }
		if ( empty( $this->data[ $post_type ]['options'] ) ) {
			$this->data[ $post_type ]['options'] = get_option( $this->plugin_prefix . '_' . $post_type . '_options' );
			if ( empty( $this->data[ $post_type ]['options'] ) ) {
				$default_options = $this->data_loader->load( 'default_settings' );
				update_option( $this->plugin_prefix . '_' . $post_type . '_options', $default_options );
				$this->data[ $post_type ]['options'] = $default_options;
			}
		}

		if ( ! is_string( $field ) ) {
			return null;
		}

		$field = strval( $field );

		if ( empty( $field ) ) {
			return $this->data[ $post_type ]['options'];
		}

		return isset( $this->data[ $post_type ]['options'][ $field ] ) ? $this->data[ $post_type ]['options'][ $field ] : null;

	}

	/**
	 * Updates plugin options
	 * @since  0.1
	 * @access public
	 * @param  mixed $option
	 * @param  string $key
	 * @return void
	 */
	public function update_option( $option, $key = null ) {

		if ( is_null( $key ) ) {
			$this->data['options'] = $option;
		} else {
			if ( 'attributes' == $key ) {
				$this->data['attributes'] = $option;
				update_option( 'bkng_attributes', $option );
			}
			if ( isset( $_GET['post_type'] ) && isset ( $_GET['page'] ) && $this->plugin_prefix . '_' . $_GET['post_type'] . '_settings' == $_GET['page'] ) {
				$this->update_post_type_option( $option, $key, sanitize_text_field( stripslashes( $_GET['post_type'] ) ) );
			}
			$this->data['options'][ sanitize_title( strval( $key ) ) ] = $option;
		}

		update_option( $this->plugin_prefix . '_options', $this->data['options'] );
	}

	/**
	 * Updates plugin options
	 * @since  0.1
	 * @access public
	 * @param  mixed $option
	 * @param  string $key
	 * @return bool
	 */
	public function update_post_type_option( $post_type, $option, $key = null ) {
		if ( is_array( $post_type ) ) {
			$post_type = $post_type[0];
		}
		if ( is_null( $key ) ) {
			$this->data[ $post_type ]['options'] = $option;
		} else {
			$this->data[ $post_type ]['options'][ sanitize_title( strval( $key ) ) ] = $option;
		}
		return update_option( $this->plugin_prefix . '_' . $post_type . '_options', $this->data[ $post_type ]['options'] );
	}

	/**
	 * Includes Google Map scripts to the site
	 * @since  0.1
	 * @access public
	 * @param  array $options
	 * @return void
	 */
	public function add_google_map_scripts() {
		$key           = $this->get_option( 'google_map_key' );
		$language      = $this->get_option( 'google_map_language' );
		$region        = $this->get_option( 'google_map_region' );
		$auto_language = $this->get_option( 'google_map_auto_detect' );
		$key_param     = empty( $key ) ? '' : "&key={$key}";
		$lang_param    = empty( $language ) || $auto_language ? "" : "&language={$language}";
		$region_param  = empty( $region ) ? "" : "&region={$region}";
		$loc_data     = array(
			'default_lat'     => $this->get_option( 'google_map_default_lat' ),
			'default_lng'     => $this->get_option( 'google_map_default_lng' ),
			'default_address' => $this->get_option( 'google_map_default_address' ),
			'find_error'      => __( 'Cannot find the address on the map', BWS_BKNG_TEXT_DOMAIN ) . '.',
			'is_admin'        => !!$this->is_admin()
		);

		wp_register_script(
			'bkng_google_map_api',
			"https://maps.googleapis.com/maps/api/js?{$key_param}{$lang_param}{$region_param}",
			array(),
			false,
			true
		);

		wp_enqueue_script(
			'bkng_google_map_handle',
			BWS_BKNG_URL . 'js/map_handle.js',
			array( 'jquery', 'bkng_google_map_api' ),
			false,
			true
		);

		wp_localize_script( 'bkng_google_map_handle', 'bws_bkng_map', $loc_data );
	}

	/**
	 * Includes Date timepicker scripts to the site
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function add_datepicker_scripts() {
		$url = BWS_BKNG_URL . '/assets/datetimepicker/';
		wp_enqueue_style( 'bkng_datepicker_style', "{$url}jquery.datetimepicker.min.css" );
		wp_enqueue_script( 'bkng_datepicker_script', "{$url}jquery.datetimepicker.full.min.js", array( 'jquery' ) );
	}

	/**
	 * Set image sizes
	 * @since  0.1
	 * @param  void
	 * @return void
	 */
	public function set_image_sizes() {
		global $bws_post_type, $bws_admin_menu_pages;
		$current_post_types = array_keys( $bws_post_type );
		foreach( $current_post_types as $post_type ) {
            if( isset( $bws_admin_menu_pages[ $post_type ]['settings_page'] ) ) {
				$crop = $this->get_post_type_option( $post_type, 'crop_images' );
				if ( $crop ) {
					$crop_position = $this->get_post_type_option( $post_type, 'crop_position' );
					$crop = array( $crop_position['horizontal'], $crop_position['vertical'] );
				}

				foreach ( array( 'catalog', 'thumbnails' ) as $slug ) {
					$size = $this->get_post_type_option( $post_type, $slug . '_image_size' );
					add_image_size( 'bkng_' . $slug . '_' . $post_type . '_image', $size['width'], $size['height'], $crop );
				}
			}
		}
	}

	/**
	 * Magic method
	 * @since  0.1
	 * @access public
	 * @param  string
	 * @param  string
	 * @return void
	 */
	public function __set( $name, $value ) {
		$this->data[ $name ] = $value;
	}

	/**
	 * Magic method
	 * @since  0.1
	 * @access public
	 * @param  string
	 * @return mixed
	 */
	public function __get( $name ) {
		return isset( $this->data[ $name ] ) ? $this->data[ $name ] : null;
	}

	/**
	 * Magic method
	 * @since  0.1
	 * @access public
	 * @param  string
	 * @return mixed
	 */
	public function __call( $name, $args ) {
		foreach ( $this->helpers as $helper ) {
			if ( method_exists( $helper, $name ) )
				return call_user_func_array( array( $helper, $name ), $args );
		}
		return null;
	}

	/**
	 * Magic method
	 * @since  0.1
	 * @access public
	 * @param  string
	 * @return boolean
	 */
	public function __isset( $name ) {
		return isset( $this->data[ $name ] );
	}

	public function plugin_update_row( $file, $plugin_data ) {
		bws_plugin_update_row( $this->plugin_basename );
	}

	public function inject_info( $result, $action = null, $args = null ) {
		if ( ! function_exists( 'bestwebsoft_inject_info' ) )
			require_once( plugin_dir_path( $this->plugin_file ) . 'bws_update.php' );

		return bestwebsoft_inject_info( $result, $action, $args, pathinfo( $this->plugin_file, PATHINFO_FILENAME ) );
	}

	public function update_activate() {
		global $bstwbsftwppdtplgns_options, $bestwebsoft_wp_update_plugins;

		$free = str_replace( '-pro', '', $this->plugin_basename );

		if ( function_exists( 'is_multisite' ) && is_multisite() && ! is_plugin_active_for_network( $this->plugin_basename ) )
			$deactivate_not_for_all_network = true;

		if ( isset( $deactivate_not_for_all_network ) && is_plugin_active_for_network( $free ) ) {
			global $wpdb;
			deactivate_plugins( $free );

			$old_blog = $wpdb->blogid;
			/* Get all blog ids */
			$blogids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs};" );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				activate_plugin( $free );
			}
			switch_to_blog( $old_blog );
		} else {
			deactivate_plugins( $free );
		}

		/* Api for update bws plugins */
		if ( ! function_exists( 'bestwebsoft_wp_update_plugins' ) && ! $bestwebsoft_wp_update_plugins ) {
			$bestwebsoft_wp_update_plugins = true;
			require_once( plugin_dir_path( $this->plugin_file ) . '/bws_update.php' );
		}

		if ( ! isset( $bstwbsftwppdtplgns_options ) ) {
			if ( is_multisite() ) {
				if ( ! get_site_option( 'bstwbsftwppdtplgns_options' ) ) {
					add_site_option( 'bstwbsftwppdtplgns_options', array() );
				}
				$bstwbsftwppdtplgns_options = get_site_option( 'bstwbsftwppdtplgns_options' );
			} else {
				if ( ! get_option( 'bstwbsftwppdtplgns_options' ) ) {
					add_option( 'bstwbsftwppdtplgns_options', array() );
				}
				$bstwbsftwppdtplgns_options = get_option( 'bstwbsftwppdtplgns_options' );
			}
		}

		if ( $bstwbsftwppdtplgns_options && ! file_exists( plugin_dir_path( $this->plugin_file ) . 'license_key.txt' ) ) {
			if ( isset( $bstwbsftwppdtplgns_options[ $this->plugin_basename ] ) ) {
				$bws_license_key = $bstwbsftwppdtplgns_options[ $this->plugin_basename ];
				$file            = @fopen( plugin_dir_path( $this->plugin_file ) . 'license_key.txt', 'w+' );
				if ( $file ) {
					@fwrite( $file, $bws_license_key );
					@fclose( $file );
				}
			}
		}
	}

	/**
	 * Clears the plugin data during the blog removing.
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function delete_blog( $blog_id ) {
		global $wpdb;

		if ( ! is_plugin_active_for_network( $this->plugin_basename ) )
			return;

		$old_blog = $wpdb->blogid;
		switch_to_blog( $blog_id );
		BWS_BKNG_Uninstaller::uninstall( true );
		switch_to_blog( $old_blog );
	}

	/**
	 * Adds helper classes during the plugin initialization
	 * @see
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return void
	 */
	private function init_helpers() {
		$helpers = array(
			'BWS_BKNG_Helper', 'BWS_BKNG_HTML_Helper', 'BWS_BKNG_Validators'
		);
		foreach( $helpers as $class )
			$this->add_helper( $class );

		if ( $this->is_admin() )
			$this->add_helper( 'BWS_BKNG_Settings_Helper' );
	}

	/**
	 * Loads the files that contain the Booking core functions
	 * @see
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return void
	 */
	private function init_functions() {
		$files = array( 'agencies', 'cart', 'categories', 'general', 'orders', 'products', 'search' );
		$path  = BWS_BKNG_PATH . "tags/%s.php";
		foreach ( $files as $file )
			require sprintf( $path, $file );
	}

	/**
	 * Some magic to avoid the creation of several instances of this class
	 * @since  0.1
	 */
	private function __clone()  {}
	private function __sleep()  {}
	private function __wakeup() {}
}
