<?php
/**
 * Contains the functionality to implement a compatibility with other WP plugins
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Related_Plugins' ) )
	return;

class BWS_BKNG_Related_Plugins {

	/**
	 * The tag <input> attr 'name' value
	 * @uses   on the Booking core settings page
	 * @since  0.1
	 * @static
	 * @access public
	 * @var    string
	 */
	public static $input_name = 'bkng_related_plugin';

	/**
	 * The list of plugins that are compatibile with the Booking core
	 * @since  0.1
	 * @static
	 * @access private
	 * @var    array
	 */
	private $plugins;

	/**
	 * The list of errors
	 * @since  0.1
	 * @static
	 * @access private
	 * @var    array
	 */
	private $errors = array();

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

	/**
	 * Fetch the related plugins data
	 * @since    0.1
	 * @access   public
	 * @param    string   $plugin   The slug of the related plugin
	 * @return   array              Selected plugin data, the list of all related plugins data otherwise
	 */
	public function get( $plugin = '') {
		return empty( $plugin ) || ! array_key_exists( $plugin, $this->plugins ) ? $this->plugins : $this->plugins[ $plugin ] ;
	}

	/**
	 * Fetch the related plugins status
	 * @since    0.1
	 * @access   public
	 * @param    string           $plugin   The slug of the related plugin ( 'installed', 'active', 'outdated', 'enabled' )
	 * @return   boolean|string
	 */
	public function is( $plugin, $status = 'enabled' ) {
		if ( ! array_key_exists( $plugin, $this->plugins ) || ! array_key_exists( $status, $this->plugins[ $plugin ]['status'] ) )
			return false;

		return $this->plugins[ $plugin ]['status'][ $status ];
	}

	/**
	 * Save the related plugin options
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function save_options() {
		foreach( $this->plugins as $plugin_name => $plugin_data ) {

			if ( ! $plugin_data['status']['active'] || $plugin_data['status']['outdated'] )
				continue;

			$is_enabled = ! empty( $_POST[ self::$input_name ][ $plugin_name ] );

			$this->plugins[ $plugin_name ]['status']['enabled'] = $is_enabled;

			$plugin_options = $this->get_option( $plugin_data['options_name'] );

			switch( $plugin_name ) {
				case 'captcha':
					if ( isset( $plugin_options['forms'][ BWS_BKNG_TEXT_DOMAIN ] ) ) {
						$plugin_options['forms'][ BWS_BKNG_TEXT_DOMAIN ]['enable'] = $is_enabled;
					} else {
						$plugin_options['forms'][ BWS_BKNG_TEXT_DOMAIN ] = array(
							'use_general'			=> true,
							'enable'				=> $is_enabled,
							'hide_from_registered'	=> false,
							'enable_time_limit'		=> 120
						);
					}
					break;
				case 'recaptcha':
				case 'slider':
					$plugin_options[ BWS_BKNG_TEXT_DOMAIN ] = absint( $is_enabled );
					break;
				default:
					break;
			}

			update_option( $plugin_data['options_name'], $plugin_options );
		}
	}

	/**
	 * Register hooks for plugins compatibility
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public static function init_hooks() {
		$instance = self::get_instance();

		/**************************************
		******** for CAPTCHA by BWS ***********
		**************************************/
		/* to display "Product" checkbox on the CAPTCHA settings page */
		add_filter( 'cptch_add_form', array( $instance, 'add_captcha' ) );
		if ( $instance->is( 'captcha' ) ) {
			add_action( 'bws_bkng_checkout_form_before_submit_button', array( $instance, 'display_captcha' ) );
			add_filter( 'bws_bkng_check_billing_data', array( $instance, 'check_captcha' ) );
			add_filter( 'bws_bkng_order_errors', array( $instance, 'add_captcha_error' ), 10, 2 );
		}

		/**************************************
		******** for Slider by BWS ************
		**************************************/
		/* to display "Product" checkbox on the Slider settings page */
		add_action( 'Bws_Settings_Tabs_after_tab_settings', array( $instance, 'add_slider_options' ) );
		add_filter( "pre_update_option_{$instance->plugins['slider']['options_name']}", array( $instance, 'save_slider_options' ) );
		if ( $instance->is( 'slider' ) ) {
			add_filter( 'sldr_request_options', array( $instance, 'prepare_slider_options' ) );
			add_action( 'sldr_after_content', array( $instance, 'add_search_form_to_slider' ), 10, 2 );
		}
	}

	/**************************************
	 ******** for CAPTCHA by BWS **********
	 **************************************/
	/**
	 * Adds captcha the Booking core option to the CAPTCHA by BWS settings page
	 * @since    0.1
	 * @access   public
	 * @param    array    The list of forms slugs compatible with the CAPTCHA by BWS
	 * @return   array
	 */
	public function add_captcha( $forms ) {
		global $bws_bkng;
		$info = $bws_bkng->get_plugin_info();
		$forms[ BWS_BKNG_TEXT_DOMAIN ] = $info['Name'];
		return $forms;
	}

	/**
	 * Displays the CAPTCHA by BWS filed ( in the checkout form )
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function display_captcha() { ?>
		<div<?php echo bws_bkng_is_error( 'captcha_error', bws_bkng_get_errors( 'checkout', true ) ) ? ' class="bws_bkng_error_input_wrap"' : ''; ?>>
			<?php echo apply_filters( 'cptch_display', '', BWS_BKNG_TEXT_DOMAIN ); ?>
		</div>
	<?php }

	/**
	 * Checks the CAPTCHA answer during the checkout form submitting
	 * @since    0.1
	 * @access   public
	 * @param    boolean  $allow      Whether the checkout form data are correct
	 * @return   boolean
	 */
	public function check_captcha( $allow ) {

		if ( ! $allow )
			return false;

		$error = apply_filters( 'cptch_verify', true, 'string', BWS_BKNG_TEXT_DOMAIN );

		if ( true !== $error ) {
			$this->errors[ 'captcha_error' ] = $error;
			return 'captcha_error';
		}

		return true;
	}

	/**
	 * Adds the CAPTCHA error message to the errors occurred on the checkout page
	 * @since    0.1
	 * @access   public
	 * @param    string      $message      The error message
	 * @param    string      $code         The error code
	 * @return   boolean
	 */
	public function add_captcha_error( $message, $code ) {
		return $message . ( 'captcha_error' == $code && ! ( empty( $this->errors[ 'captcha_error' ] ) ) ? $this->errors[ 'captcha_error' ] : '' );
	}

	/**************************************
	 ******** for Slider by BWS ***********
	 **************************************/
	/**
	 * Displays the plugin core additional options
	 * on the Slider by BWS settings page and on the sliders edit pages (via bws_menu)
	 * @since    0.1
	 * @access   public
	 * @param    object      $instance      The instance of the class that handle the settings pages content
	 * @return   void
	 */
	public function add_slider_options( $instance ) {
		global $bws_bkng;

		if ( ! $instance instanceof Sldr_Settings_Tabs )
			return;

		if ( $instance->is_general_settings ) {
			if ( $this->is( 'slider', 'outdated' ) ) {
				$message = sprintf(
					__( '%s is outdated. Update it to the latest version', BWS_BKNG_TEXT_DOMAIN ) . '.',
					$this->plugins['slider']['name']
				);
				$attr = 'disabled="disabled"';
			} else {
				$is_checked = get_option( 'sldr_options' );
				$is_checked = $is_checked['bws_booking'];
				$attr       = $is_checked ? ' checked="checked"' : '';
			}
			$info  = $bws_bkng->get_plugin_info();
			$label = __( 'Enable Slider for', BWS_BKNG_TEXT_DOMAIN );
			$name  = "sldr_" . BWS_BKNG_TEXT_DOMAIN;
			$after = $info['Name'];
			$content = $bws_bkng->get_checkbox( compact( 'name', 'attr', 'after' ) );
			if ( ! empty( $message ) )
				$content .= "<span class=\"bws_info\">{$message}</span>";
		} else {

			if ( ! $this->is( 'slider' ) )
				return;

			$label     = __( 'Add Product Search Form', BWS_BKNG_TEXT_DOMAIN );
			$content   = '';
			$locations = array(
				'nope'   => __( "No", BWS_BKNG_TEXT_DOMAIN ),
				'center' => __( "Centered", BWS_BKNG_TEXT_DOMAIN ),
				'left'   => __( "Align Left", BWS_BKNG_TEXT_DOMAIN ),
				'right'  => __( "Align Right", BWS_BKNG_TEXT_DOMAIN )
			);
			$current = empty( $instance->options['display_bkng_form'] ) ? 'nope' : $instance->options['display_bkng_form'];
			$name    = 'sldr_display_bkng_form';
			foreach( $locations as $value => $after ) {
				$attr = $value == $current ? ' checked="checked"' : '';
				$content .= "<p>" .  $bws_bkng->get_radiobox( compact( 'name', 'value', 'attr', 'after' ) ) . "</p>";
			}
		}

		$check_slider_options = get_option( 'sldr_options' );

		if ( ! empty( $check_slider_options ) ) {
			$slider_options = get_option( 'sldr_options' );
		}
		if ( '1.0.1' != $slider_options['plugin_option_version'] ) { ?>
            <table class="form-table sldr_settings_form">
                <tr>
                    <th><?php echo $label; ?></th>
                    <td><fieldset><?php echo $content;?></fieldset></td>
                </tr>
            </table>
		<?php }
	}

	/**
	 * Adds the additional fields to the Slider by BWS plugin options
	 * @since    0.1
	 * @access   public
	 * @param    array      $options      the list of the Slider by BWS options
	 * @return   array
	 */
	public function save_slider_options( $options ) {
		if ( ! is_admin() )
			return $options;

		if ( ! isset( $_POST['sldr_form_submit'] ) ) {
			$flag = $options['bws_booking'];
		} else {
			$flag = isset( $_POST['sldr_bws_booking'] ) ? sanitize_text_field( stripslashes( $_POST['sldr_bws_booking'] ) ): 0;
		}
		$options[ BWS_BKNG_TEXT_DOMAIN ] = ( isset( $_REQUEST[ 'sldr_' . BWS_BKNG_TEXT_DOMAIN ] ) || isset( $_REQUEST[ BWS_BKNG_Related_Plugins::$input_name ] ) || $flag ) ? 1 : 0;

		return $options;
	}

	/**
	 * Adds the additional fields to the individual slider settings of the Slider by BWS plugin
	 * @since    0.1
	 * @access   public
	 * @param    array      $options      the individual slider settings
	 * @return   array
	 */
	public function prepare_slider_options( $options ) {
		$keys = array( 'nope', 'center', 'left', 'right' );
		$field = 'sldr_display_bkng_form';
		$options['display_bkng_form'] = ! empty( $_POST[ $field ] ) && in_array( $_POST[ $field ], $keys ) ? sanitize_text_field( stripslashes( $_POST[ $field ] ) ): 'nope';
		return $options;
	}

	/**
	 * Displays the products primary search form in the slider
	 * @since    0.1
	 * @access   public
	 * @param    array      $shortcode_attributes      The [print_sldr] shortcode attributes
	 * @param    array      $settings                  The displayed slider settings
	 * @return   void
	 */
	public function add_search_form_to_slider( $shortcode_attributes, $settings ) {
		if ( empty( $settings['display_bkng_form'] ) || ! in_array( $settings['display_bkng_form'], array( 'center', 'left', 'right' ) ) )
			return; ?>

		<style>
			<?php switch( $settings['display_bkng_form'] ) {
				case 'left': ?>
					.sldr_wrapper .owl-carousel.sldr_carousel_<?php echo esc_attr( $shortcode_attributes['id'] ); ?> .owl-dots {
						width: 100%;
						top: -25px;
						right: auto;
                        			left: auto;
					}
					<?php break;
				case 'right': ?>
					.sldr_wrapper .owl-carousel.sldr_carousel_<?php echo esc_attr( $shortcode_attributes['id'] ); ?> .owl-dots {
						width: 100%;
						top: -25px;
                        			right: auto;
                        			left: auto;
					}
					<?php break;
				case 'center': ?>
					.sldr_wrapper .owl-carousel.sldr_carousel_<?php echo esc_attr( $shortcode_attributes['id'] ); ?> .owl-dots {
						width: 100%;
						top: auto;
					}
				<?php break;
				default:
					break;
			}
			/**
			 * Next css-rules are added in order to avoid
			 * breaking styles for sliders without search form
			 */ ?>
			.sldr_wrapper .owl-carousel.sldr_carousel_<?php echo esc_attr( $shortcode_attributes['id'] ); ?> .owl-dots {
				position: absolute;
			}
			@media only screen and ( max-width: 782px ) {
				.sldr_wrapper .owl-carousel.sldr_carousel_<?php echo esc_attr( $shortcode_attributes['id'] ); ?> .owl-dots {
					width: 100%;
					top: auto;
					left: auto;
					right: auto
				}
			}
		</style>
		<div class="bws_bkng_search_form_wrap bws_bkng_search_form_align_<?php echo esc_attr( $settings['display_bkng_form'] ); ?>">
			<?php if ( 'right' == $settings['display_bkng_form'] ) { ?>
				<div class="bws_bkng_form_img">
					<img src="<?php echo esc_url( plugins_url( BWS_BKNG_SLUG, '' ) . '/images/slider-front.png' ); ?>">
				</div>
			<?php } ?>
			<div class="bws_bkng_search_form">
				<?php bws_bkng_get_template_part( 'forms/search-products' ); ?>
			</div>
			<?php if ( 'left' == $settings['display_bkng_form'] ) { ?>
				<div class="bws_bkng_form_img">
					<img src="<?php echo esc_url( plugins_url( BWS_BKNG_SLUG, '' ) . '/images/slider-front.png' ); ?>">
				</div>
			<?php } ?>
		</div>
		<div class="clear"></div>
	<?php }

	/**
	 * Constructor of the class
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   void
	 */
	private function __construct() {
		global $bws_bkng, $wp_version;

		$info = $bws_bkng->get_plugin_info();

		$this->plugins = array(
			'captcha' => array(
				'name'          => 'Captcha by BestWebSoft',
				'short_name'    => 'Captcha',
				'download_link' => 'https://bestwebsoft.com/products/wordpress/plugins/captcha/?k=' . $bws_bkng->plugins_link_keys['captcha'] . '&amp;pn=' . $bws_bkng->link_pn . '&amp;v=' . $info["Version"] . '&amp;wp_v=' . $wp_version,
				'link_slug'     => array(
					'free' => 'captcha-bws/captcha-bws.php',
					'plus' => 'captcha-plus/captcha-plus.php',
					'pro'  => 'captcha-pro/captcha_pro.php'
				),
				'options_name' => 'cptch_options',
				'min_version'  => '4.2.3'
			),
			/**
			 * The compatibility the Google CAPTCHA (reCAPTCHA) by BestWebSoft plugin with BWS Booking is in the development mode now.
			 *
			 * @todo After it will be done uncomment the strings below and replace the 'min_version' field value with
			 * the reCAPTCHA verison in what the compatibility with BWS Booking will be implemented.
			 * It may be possible that the mechanism of getting and saving the reCAPTCHA options in Booking also will need to be changed according to the
			 * reCAPTCHA options structure (@see self::get_plugin_status(), self::save_options()).
			 *
			 * 'recaptcha' => array(
			 * 	'name'          => 'Google Captcha (reCAPTCHA) by BestWebSoft',
			 * 	'short_name'    => 'Google Captcha',
			 * 	'download_link' => 'https://bestwebsoft.com/products/wordpress/plugins/google-captcha/?k=' . $bws_bkng->plugins_link_keys['recaptcha'] . '&amp;pn=' . $bws_bkng->link_pn . '&amp;v=' . $info["Version"] .  * '&amp;wp_v=' . $wp_version,
			 * 	'link_slug'     => array(
			 * 		'free' => 'google-captcha/google-captcha.php',
			 * 		'pro'  => 'google-captcha-pro/google-captcha-pro.php'
			 * 	),
			 * 	'options_name' => 'gglcptch_options',
			 * 	'min_version'  => '1.30',
			 * )
			 */
			'slider' => array(
				'name'          => 'Slider by BestWebSoft',
				'short_name'    => 'Slider',
				'download_link' => 'https://bestwebsoft.com/products/wordpress/plugins/slider/',
				'link_slug'     => array(
					'free' => 'slider-bws/slider-bws.php'
				),
				'options_name' => 'sldr_options',
				'min_version'  => '1.0.1'
			)
		);

		$this->add_statuses();
	}

	/**
	 * Adds plugins statuses to their data
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   void
	 */
	private function add_statuses() {
		foreach( $this->plugins as $plugin_name => $data )
			$this->plugins[ $plugin_name ]['status'] = $this->get_plugin_status( $plugin_name );
	}

	/**
	 * Checks the plugin status
	 * @since    0.1
	 * @access private
	 * @param  string    $plugin name       Shared name of the plugin, the self::plugins array key
	 *                                     ( @see self::__construct() )
	 * @return array     $status           The plugin status data. Format:
	 *                                     {status_slug} => {true if the status is actual | false otherwise}
	 */
	private function get_plugin_status( $plugin_name ) {

		$status = array(
			'installed' => false,
			'active'    => false,
			'outdated'  => false,
			'enabled'   => false,
		);

		if ( empty( $plugin_name ) || ! array_key_exists( $plugin_name, $this->plugins ) )
			return $status;

		$plugin = $this->plugins[ $plugin_name ];

		if ( ! function_exists( 'get_plugins' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		$all_plugins = get_plugins();

		$status['installed'] = !! array_intersect( $plugin['link_slug'], array_keys( $all_plugins ) );

		if ( ! $status['installed'] )
			return $status;

		foreach ( $plugin['link_slug'] as $version => $link_slug ) {
			if ( is_plugin_active( $link_slug ) ) {
				$status['active'] = $version;
				break;
			}
		}

		if ( ! $status['active'] )
			return $status;

		$plugin_options = $this->get_option( $plugin['options_name'] );

		if ( empty( $plugin_options ) )
			return $status;


		if ( isset( $plugin_options['plugin_option_version'] ) && ! empty( $plugin['min_version'] ) )
			$status['outdated'] = version_compare( str_replace( 'pro-', '', $plugin_options['plugin_option_version'] ), $plugin['min_version'], '<' );

		if ( $status['outdated'] )
			return $status;

		switch( $plugin_name ) {
			case 'captcha':
				$status['enabled'] = ! empty( $plugin_options['forms'][ BWS_BKNG_TEXT_DOMAIN ]['enable'] );
				break;
			case 'recaptcha':
			case 'slider':
				$status['enabled'] = ! empty( $plugin_options[ BWS_BKNG_TEXT_DOMAIN ] );
				break;
		}

		return $status;
	}

	/**
	 * Fetch the related plugins options
	 * @since  0.1
	 * @access private
	 * @param  string    $option_name      The plugin option name
	 * @return array     $option           The plugin options
	 */
	private function get_option( $option_name ) {
		if ( is_multisite() ) {
			$option = get_site_option( $option_name );

			/**
			 * Fetch each blog options in case if:
			 * - there are not network options;
			 * - the 'network_apply' option value is incorrect, absent or is't set yet
			 * - it is allowed in the network options to manage each blog options separately
			 */
			if ( ! $option || empty( $option['network_apply'] ) || 'all' != $option['network_apply'] )
				$option = get_option( $option_name );

		} else {
			$option = get_option( $option_name );
		}

		return $option;
	}

	/**
	 * Some magic to avoid the creation of several instances of this class
	 * @since  0.1
	 */
	private function __clone()  {}
	private function __sleep()  {}
	private function __wakeup() {}

}
