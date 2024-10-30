<?php
/**
 * Handle the content of "General" tab
 * @since  Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Settings_General' ) )
	return;

class BWS_BKNG_Settings_General extends BWS_BKNG_Settings_Tabs {

	/**
	 * Contains the list of plugins with which the Booking core interacts
	 * @since  0.1
	 * @var    array
	 * @access private
	 */
	private $related_plugins;

	/**
	 * Contains the flag for general setting page
	 * @since  0.1
	 * @var    bool
	 * @access private
	 */
	private $is_general_settings;

	/**
	 * Class constructor
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function __construct() {
		global $bws_plugin_settings_subtabs;

        if( ( isset( $_GET['page'] ) && 'bkng_general_settings' == $_GET['page'] ) && ( ! isset( $_GET['action'] ) || 'general' == $_GET['action'] ) ) {
			$this->is_general_settings = true;
		} else {
			$this->is_general_settings = false;
		}

		if( true === $this->is_general_settings ) {
			$this->tabs = array(
				'general'                 => array( 'label' => __( 'General', BWS_BKNG_TEXT_DOMAIN ) ),
				'checkout'                => array( 'label' => __( 'Checkout', BWS_BKNG_TEXT_DOMAIN ) ),
				'cart'                    => array( 'label' => __( 'Cart', BWS_BKNG_TEXT_DOMAIN ) ),
				'customer_notifications'  => array( 'label' => __( 'Customer Notifications', BWS_BKNG_TEXT_DOMAIN ) ),
				'agent_notifications'     => array( 'label' => __( 'Agent Notifications', BWS_BKNG_TEXT_DOMAIN ) ),
				'custom_code'             => array( 'label' => __( 'Custom Code', BWS_BKNG_TEXT_DOMAIN ) ),
			);
		} else {
			$this->tabs = array(
				'images'        => array( 'label' => __( 'Images', BWS_BKNG_TEXT_DOMAIN ) ),
				'slider'		=> array( 'label' => __( 'Slider', BWS_BKNG_TEXT_DOMAIN ) ),
			);

			$this->tabs = ! empty( $this->tabs ) ? array_merge( $this->tabs,  $bws_plugin_settings_subtabs ) : $bws_plugin_settings_subtabs;
			$this->tabs = array_merge( $this->tabs, array(
				'misc'          => array( 'label' => __( 'Misc', BWS_BKNG_TEXT_DOMAIN ) ),
				'import-export' => array( 'label' => __( 'Import / Export', BWS_BKNG_TEXT_DOMAIN ) ),
				'license'       => array( 'label' => __( 'License Key', BWS_BKNG_TEXT_DOMAIN ) ),
			) );
		}

		parent::__construct();

		$this->related_plugins = BWS_BKNG_Related_Plugins::get_instance();

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
		$currencies         = $bws_bkng->data_loader->load( 'currencies' );
		$currency_positions = array( 'left', 'right' );
		$price_types        = array( 'basic', 'seasons' );
		$date_format        = array_keys( $bws_bkng->data_loader->load( 'date_format' ) );
		$map_locales        = array_keys( $bws_bkng->data_loader->load( 'map_languages' ) );
		$x_crop_vector      = array( 'left', 'center', 'right' );
		$y_crop_vector      = array( 'top', 'center', 'bottom' );

		if( true === $this->is_general_settings ) {
			/* booleans */
			$this->options['google_map_auto_detect'] = ! empty( $_POST['bkng_google_map_auto_detect'] );
			/* numbers */
			$this->options['number_decimals']        = absint( $_POST['bkng_number_decimals'] );
			$this->options['google_map_default_lat'] = floatval( $_POST['bkng_google_map_default_latitude'] );
			$this->options['google_map_default_lng'] = floatval( $_POST['bkng_google_map_default_longitude'] );

			/* !!! Do not trim() these options in order to use spaces */
			$this->options['number_thousand_separator']  = $_POST['bkng_number_thousand_separator'] == ' ' ? ' ' : sanitize_text_field( $_POST['bkng_number_thousand_separator'] );
			$this->options['number_decimal_separator']   = $_POST['bkng_number_thousand_separator'] == ' ' ? ' ' : sanitize_text_field( $_POST['bkng_number_decimal_separator'] );
			$this->options['google_map_key']             = sanitize_text_field( stripslashes( $_POST['bkng_google_map_key'] ) );
			$this->options['google_map_region']          = sanitize_text_field( stripslashes( $_POST['bkng_google_map_region'] ) );
			$this->options['google_map_default_address'] = sanitize_text_field( stripslashes( $_POST['bkng_google_map_default_address'] ) );


			if ( $this->options['number_thousand_separator'] == $this->options['number_decimal_separator'] ) {
				$this->add_message( 'same_separator', __( "Thousands and decimal separators can't be the same", BWS_BKNG_TEXT_DOMAIN ), 'notices' );
				$this->options['number_thousand_separator'] = $bws_bkng->get_option( 'number_thousand_separator' );
				$this->options['number_decimal_separator']  = $bws_bkng->get_option( 'number_decimal_separator' );
			}
			if ( preg_match( '/[0-9]+/', $this->options['number_thousand_separator'] ) ) {
				$this->add_message( 'digital_thousand_separator', __( "Thousands separator mustn't contain numbers", BWS_BKNG_TEXT_DOMAIN ), 'notices' );
				$this->options['number_thousand_separator'] = $bws_bkng->get_option( 'number_thousand_separator' );
			}
			if ( preg_match( '/[0-9]+/', $this->options['number_decimal_separator'] ) ) {
				$this->add_message( 'digital_decimal_separator', __( "Decimal separator  mustn't contain numbers", BWS_BKNG_TEXT_DOMAIN ), 'notices' );
				$this->options['number_decimal_separator'] = $bws_bkng->get_option( 'number_decimal_separator' );
			}

			/* variations */
			$this->options['currency_code']       = in_array( $_POST['bkng_currency_code'], array_keys( $currencies ) ) ? sanitize_text_field( $_POST['bkng_currency_code'] ) : $this->options['currency_code'];
			$this->options['currency_position']   = in_array( $_POST['bkng_currency_position'], $currency_positions ) ? sanitize_text_field( $_POST['bkng_currency_position'] ) : $this->options['currency_position'];
			$this->options['price_type']          = isset( $_POST['bkng_price_type'] ) && in_array( $_POST['bkng_price_type'], $price_types ) ? sanitize_text_field( $_POST['bkng_price_type'] ) : $this->options['price_type'];
			$this->options['date_format']         = in_array( $_POST['bkng_date_format'], $date_format ) ? sanitize_text_field( $_POST['bkng_date_format'] ) : $this->options['date_format'];
			$this->options['google_map_language'] = in_array( $_POST['bkng_google_map_language'], $map_locales ) ? sanitize_text_field( $_POST['bkng_google_map_language'] ) : $this->options['google_map_language'];
			$this->options['currency']            = $currencies[ $this->options['currency_code'] ][1];
		} else {
			$this->options['enable_lightbox']        = ! empty( $_POST['bkng_enable_lightbox'] );
			$this->options['enable_slider']			 = ! empty( $_POST['bkng_enable_slider'] );
			$this->options['crop_images']            = ! empty( $_POST['bkng_crop_images'] );
			$this->options['display_form']           =  sanitize_text_field( $_POST['bkng_display_form'][0] );
			$this->options['display_in_slider']      = isset( $_POST['bkng_display_in_slider'] ) ? array_map( 'sanitize_text_field', $_POST['bkng_display_in_slider'] ) : array();
			$image_sizes                             = array( 'catalog_image_size', 'thumbnails_image_size' );
			foreach( $image_sizes as $size ) {
				$this->options[ $size ] = array(
					'width'  => absint( $_POST[ "bkng_{$size}" ]['width'] ),
					'height' => absint( $_POST[ "bkng_{$size}" ]['height'] )
				);
			}
			$crops = empty( $_POST['bkng_crop_position'] ) ? array( 'center', 'center' ) : explode( '-', sanitize_text_field( $_POST['bkng_crop_position'] ) );
			$this->options['crop_position'] = array(
				'vertical'   => ( ! empty( $crops[0] ) && in_array( $crops[0], $y_crop_vector ) ? $crops[0] : $this->options['crop_position']['vertical'] ),
				'horizontal' => ( ! empty( $crops[1] ) && in_array( $crops[1], $x_crop_vector ) ? $crops[1] : $this->options['crop_position']['horizontal'] )
			);
		}
		$this->options['hide_pro_tabs']  = isset( $_POST['bws_hide_premium_options_submit'] ) ? 'false' : 'true';

		$classes = array( 'BWS_BKNG_Settings_Cart', 'BWS_BKNG_Settings_Emails', 'BWS_BKNG_Settings_Checkout' );

		if ( isset( $_GET['action'] ) && $_GET['action'] !== 'general' ) {
            $classes = array( 'BWS_BKNG_Settings_Products' );
        }
		foreach ( $classes as $class ) {
			$page  = new $class();
			$this->options = $page->prepare_options( $this->options );
		}
		$this->related_plugins->save_options();
	}

	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function tab_general() {
		global $bws_bkng;
		$this->tab_title( __( 'General Settings', BWS_BKNG_TEXT_DOMAIN ) ); ?>
		<table class="form-table bkng_general_settings_tab_table">
			<div class="bws_tab_sub_label fcbkbttn_general_enabled"><?php _e( 'General', 'facebook-button-pro' ); ?></div>
			<?php /**
			 * Currency option
			 */
			$name     = 'bkng_currency_code';
			$selected = $this->options['currency_code'];
			$options  = $bws_bkng->get_currencies();
			$content  = $bws_bkng->get_select( compact( 'name', 'options', 'selected' ) );

			$bws_bkng->display_table_row( __( 'Currency', BWS_BKNG_TEXT_DOMAIN ), $content );

			/**
			 * Currency position option
			 */
			$name  = 'bkng_currency_position';
			$value = 'left';
			$attr  = $value == $this->options['currency_position'] ? ' checked="checked"' : '';
			$after = __( 'Before numerals', BWS_BKNG_TEXT_DOMAIN );
			$left_radiobox = $bws_bkng->get_radiobox( compact( 'name', 'value', 'attr', 'after' ) );

			$name  = 'bkng_currency_position';
			$value = 'right';
			$attr  = $value == $this->options['currency_position'] ? ' checked="checked"' : '';
			$after = __( 'After numerals', BWS_BKNG_TEXT_DOMAIN );
			$right_radiobox = $bws_bkng->get_radiobox( compact( 'name', 'value', 'attr', 'after'  ) );

			$content ="<p>{$left_radiobox}</p><p>{$right_radiobox}</p>";

			$bws_bkng->display_table_row( __( 'Currency Position', BWS_BKNG_TEXT_DOMAIN ), $content );
			?>

        <?php if ( ! $bws_bkng->is_pro ) { ?>
            </table>
            <?php if ( ! $this->hide_pro_tabs ) { ?>
                <div class="bws_pro_version_bloc">
                    <div class="bws_pro_version_table_bloc">
                        <button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'captcha-bws' ); ?>"></button>
                        <div class="bws_table_bg"></div>
                        <table class="form-table bkng_general_settings_tab_table bws_pro_version">
                            <?php

                            /**
                             * Type of price  option
                             */

                            if ( ! isset( $this->options['price_type'] ) ) {
                                $bws_bkng->update_option( 'basic', 'price_type' );
                                $this->options['price_type'] = 'basic';
                            }

                            $name = "";
                            $value = 'basic';
                            $attr  = ' checked="checked"';
                            $attr .= 'disabled';
                            $after = __( 'Basic price', BWS_BKNG_TEXT_DOMAIN );
                            $basic_radiobox = $bws_bkng->get_radiobox( compact( 'name', 'value', 'attr', 'after' ) );
                            $value = 'seasons';
                            $attr = 'disabled';
                            $after = __( 'Seasons price', BWS_BKNG_TEXT_DOMAIN );
                            $seasons_radiobox = $bws_bkng->get_radiobox( compact( 'name', 'value', 'attr', 'after'  ) );

                            $content ="<p>{$basic_radiobox}</p><p>{$seasons_radiobox}</p>";
                            $bws_bkng->display_table_row( __( 'Price Type', BWS_BKNG_TEXT_DOMAIN ), $content );
                            ?>
                        </table>
                    </div>
                    <?php $this->bws_pro_block_links(); ?>
                </div>
            <?php } ?>
            <table class="form-table bkng_general_settings_tab_table">
        <?php } else {
            /**
             * Type of price  option
             */

            if ( ! isset( $this->options['price_type'] ) ) {
                $bws_bkng->update_option( 'basic', 'price_type' );
                $this->options['price_type'] = 'basic';
            }

            $name  = 'bkng_price_type';
            $value = 'basic';
            $attr  = $value == $this->options['price_type'] ? ' checked="checked"' : '';
            $after = __( 'Basic price', BWS_BKNG_TEXT_DOMAIN );
            $basic_radiobox = $bws_bkng->get_radiobox( compact( 'name', 'value', 'attr', 'after' ) );

            $name  = 'bkng_price_type';
            $value = 'seasons';
            $attr  = $value == $this->options['price_type'] ? ' checked="checked"' : '';
            $after = __( 'Seasons price', BWS_BKNG_TEXT_DOMAIN );
            $seasons_radiobox = $bws_bkng->get_radiobox( compact( 'name', 'value', 'attr', 'after'  ) );

            $content ="<p>{$basic_radiobox}</p><p>{$seasons_radiobox}</p>";

            $bws_bkng->display_table_row( __( 'Price Type', BWS_BKNG_TEXT_DOMAIN ), $content );

        }
			/**
			 * Currency thousand separator option
			 */
			$class     = 'bkng_short_input';
			$name      = 'bkng_number_thousand_separator';
			$value     = esc_html( $this->options['number_thousand_separator'] );
			$maxlength = 3;

			$content = $bws_bkng->get_text_input( compact( 'class', 'name', 'value', 'maxlength' ) );

			$bws_bkng->display_table_row( __( 'Thousands Separator', BWS_BKNG_TEXT_DOMAIN ), $content );

			/**
			 * Currency decimal separator option
			 */
			$class     = 'bkng_short_input';
			$name      = 'bkng_number_decimal_separator';
			$value     = $this->options['number_decimal_separator'];
			$maxlength = 3;

			$content = $bws_bkng->get_text_input( compact( 'class', 'name', 'value', 'maxlength' ) );

			$bws_bkng->display_table_row( __( 'Decimal Separator', BWS_BKNG_TEXT_DOMAIN ), $content );

			/**
			 * Currency decimal numbers
			 */
			$class = 'bkng_short_input';
			$name  = 'bkng_number_decimals';
			$value = $this->options['number_decimals'];
			$max   = 10;

			$content = $bws_bkng->get_number_input( compact( 'class', 'name', 'value', 'max' ) );

			$bws_bkng->display_table_row( __( 'Number of Decimal Places', BWS_BKNG_TEXT_DOMAIN ), $content );

			/**
			 * Date format
			 */
			$name     = 'bkng_date_format';
			$selected = isset( $this->options['date_format'] ) ? $this->options['date_format'] : get_option( 'date_format' );
			$options  = $bws_bkng->get_date_format();
			$content  = $bws_bkng->get_select( compact( 'name', 'options', 'selected' ) );

			$bws_bkng->display_table_row( __( 'Date Format', BWS_BKNG_TEXT_DOMAIN ), $content );

			$info = $bws_bkng->get_plugin_info();

			foreach ( $this->related_plugins->get() as $plugin_name => $plugin_data ) {
				$status    = $plugin_data['status'];
				$id        = "bkng_enable_{$plugin_name}";
				$name      = BWS_BKNG_Related_Plugins::$input_name . "[" . $plugin_name . ']';
				$attr      = !! $status['enabled'] ? 'checked="checked"' : '' ;
				$attr     .= $status['active'] && ! $status['outdated'] ? '' : ' disabled="disabled"';
				$checked   = '';
				$checkbox  = $bws_bkng->get_checkbox( compact( 'id', 'name', 'checked', 'attr' ) );

				$row_label = "<label for=\"{$id}\">{$plugin_data['short_name']}</label>";

				if ( ! $status['installed'] ) {
					$message = sprintf(
						'%3$s <a href="%1$s" target="_blank">%2$s</a>.',
						$plugin_data['download_link'],
						__( 'Install Now', BWS_BKNG_TEXT_DOMAIN ),
						$plugin_data['name']
					);
				} elseif ( ! $status['active'] ) {
					$message = sprintf(
						'%3$s <a href="%1$s" target="_blank">%2$s</a>.',
						network_admin_url( 'plugins.php' ),
						__( 'Activate', BWS_BKNG_TEXT_DOMAIN ),
						$plugin_data['name']
					);
				} elseif ( ! $status['outdated'] ) {
					$message = sprintf(
						__( 'Enable to use %s with %s', BWS_BKNG_TEXT_DOMAIN ) . '.',
						$plugin_data['short_name'],
						$info['Name']
					);
				} else {
					$message = sprintf(
						__( 'Your %s plugin is outdated. Update it to the latest version', BWS_BKNG_TEXT_DOMAIN ) . '.',
						$plugin_data['name']
					);
				}
				$bws_bkng->display_table_row( $row_label, "{$checkbox}<span class=\"bws_info\">{$message}</span>" );
			}
			?>
		</table>
		<table class="form-table">
			<?php if( true === $this->is_general_settings ) {
				?>
				<div class="bws_tab_sub_label fcbkbttn_general_enabled"><?php _e( 'Google Map Options', 'facebook-button-pro' ); ?></div>
				<?php
				//$bws_bkng->display_table_row( __( 'Google Map Options', BWS_BKNG_TEXT_DOMAIN ), '' );
				/**
				 * Google map key option
				 */
				$name  = 'bkng_google_map_key';
				$value = $this->options['google_map_key'];

				$content = $bws_bkng->get_text_input( compact( 'name', 'value' ) );

				$bws_bkng->display_table_row( __( 'Key', BWS_BKNG_TEXT_DOMAIN ), $content );

				/**
				 * Google map language option
				 */
				$name  = 'bkng_google_map_auto_detect';
				$value = 1;
				$after = __( 'Auto (using WordPress locale)', BWS_BKNG_TEXT_DOMAIN );
				$attr  = $this->options['google_map_auto_detect'] ? ' checked="checked"' : '';
				$auto_detect_radiobox = $bws_bkng->get_radiobox( compact( 'name', 'value', 'attr', 'after' ) );

				$name  = 'bkng_google_map_auto_detect';
				$value = 0;
				$attr  = ! $this->options['google_map_auto_detect'] ? ' checked="checked"' : '';
				$manual_detect_radiobox = $bws_bkng->get_radiobox( compact( 'name', 'value', 'attr' ) );

				$name     = 'bkng_google_map_language';
				$selected = $this->options['google_map_language'];
				$options  = $bws_bkng->data_loader->load( 'map_languages' );

				$language_list = $bws_bkng->get_select( compact( 'name', 'options', 'selected' ) );

				$content = "<p>{$auto_detect_radiobox}</p><p>{$manual_detect_radiobox}{$language_list}</p>";

				$bws_bkng->display_table_row( __( 'Language', BWS_BKNG_TEXT_DOMAIN ), $content );

				/**
				 * Google map region option
				 */
				$name      = 'bkng_google_map_region';
				$value     = $this->options['google_map_region'];
				$maxlength = 10;

				$bws_info_text = sprintf(
					__( 'For more info see %s. To allow automatic region detection, just leave it empty', BWS_BKNG_TEXT_DOMAIN ) . '.',
					$bws_bkng->get_link( array(
						'href'   => 'https://developers.google.com/maps/documentation/javascript/localization#Region',
						'text'   => __( 'Region localization', BWS_BKNG_TEXT_DOMAIN ),
						'target' => true
					) )
				);

				$content = $bws_bkng->get_text_input( compact( 'name', 'value', 'maxlength' ) ) . '<div class="bws_info">' . $bws_info_text . '</div>';

				$bws_bkng->display_table_row( __( 'Region Identifier', BWS_BKNG_TEXT_DOMAIN ), $content );

				/**
				 * Google map default address option
				 */
				$class   = "bkng_address_input";
				$name    = "bkng_google_map_default_address";
				$value   = $this->options['google_map_default_address'];
				$address_input = $bws_bkng->get_text_input( compact( 'class', 'name', 'value' ) );

				$unit  = 'button';
				$class = "button bkng_find_by_address_button";
				$value = __( 'Find by address', BWS_BKNG_TEXT_DOMAIN );
				$find_by_address_button = $bws_bkng->get_form_unit( compact( 'unit', 'class', 'value' ) );

				$address_errors = $bws_bkng->get_errors( '', '', "inline bkng_js_errors bkng_find_by_address_error " . BWS_BKNG::$hidden );
				$content = $address_input . $find_by_address_button . $address_errors;

				$bws_bkng->display_table_row( __( 'Default Address', BWS_BKNG_TEXT_DOMAIN ), $content, 'bkng_map_extra_options ' ); // . BWS_BKNG::$hidden );

				/**
				 * Google map wrapper
				 */

				 if( empty( $this->options['google_map_key'] ) || empty( $this->options['google_map_region'] ) || empty( $this->options['google_map_default_address'] ) ) {
					$map_style = ' style="display: none;" ';
				 }

				$content = '<div id="bkng_map_wrap" class="bkng_map_wrap"' . $map_style . '></div>';

				$bws_bkng->display_table_row( '', $content, 'bkng_map_extra_options ' ); // . BWS_BKNG::$hidden );

				/**
				 * Google map coordinates options
				 */
				$coors_errors = $bws_bkng->get_errors( '', '', 'inline bkng_js_errors bkng_find_by_coors_error ' . BWS_BKNG::$hidden );

				$unit  = 'button';
				$class = "button bkng_find_by_coordinates_button";
				$value = __( 'Find by coordinates', BWS_BKNG_TEXT_DOMAIN );
				$find_by_coors_button = $bws_bkng->get_form_unit( compact( 'unit', 'class', 'value' ) );

				$text_fields_names = array(
					'latitude'  => array( __( 'Latitude', BWS_BKNG_TEXT_DOMAIN ), $this->options['google_map_default_lat'] ),
					'longitude' => array( __( 'Longitude', BWS_BKNG_TEXT_DOMAIN ), $this->options['google_map_default_lng'] )
				);
                $latitude = $longitude = '';
				foreach ( $text_fields_names as $field_name => $data ) {
					$after  = $data[0];
					$class  = "bkng_{$field_name}_input";
					$name   = "bkng_google_map_default_{$field_name}";
					$value  = $data[1];
					${$field_name} = $bws_bkng->get_text_input( compact( 'after', 'class', 'name', 'value' ) );
				}

				$content = "{$coors_errors}<p>{$find_by_coors_button}</p><p>{$latitude}</p><p>{$longitude}</p>";

				$bws_bkng->display_table_row( __( 'Default Coordinates', BWS_BKNG_TEXT_DOMAIN ), $content, 'bkng_map_extra_options ' ); //" . BWS_BKNG::$hidden );
			} else {

				$data = array(
					'products'   => __( 'Remove all products', BWS_BKNG_TEXT_DOMAIN ),
					'attributes' => __( 'Remove all attributes', BWS_BKNG_TEXT_DOMAIN ),
					'orders'     => __( 'Remove all orders', BWS_BKNG_TEXT_DOMAIN )
				);

				$content = '';
				foreach( $data as $key => $after ) {
					$name = "bkng_remove_{$key}";
					$attr = empty( $this->options["remove_{$key}"] ) ? '' : 'checked="checked"';
					$content .= '<p>' . $bws_bkng->get_checkbox( compact( 'name', 'attr', 'after' ) ) . '</p>';
				}
				$bws_bkng->display_table_row( __( 'Uninstall Settings', BWS_BKNG_TEXT_DOMAIN ), $content );
			} ?>
		</table>
	<?php }

	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function tab_images() {
		global $bws_bkng;
		$this->tab_title( __( 'Images Settings', BWS_BKNG_TEXT_DOMAIN ) ); ?>
		<table class="form-table">
			<?php /**
			 * Image sizes options
			 */
			$image_sizes = array(
				'catalog'    => __( "Catalog Images Size", BWS_BKNG_TEXT_DOMAIN ),
				'thumbnails' => __( "Gallery Images Size", BWS_BKNG_TEXT_DOMAIN )
			);
			foreach ( $image_sizes as $slug => $title ) {
				$size   = $bws_bkng->get_option( "{$slug}_image_size" );

				$name  = "bkng_{$slug}_image_size[width]";
				$value = $size['width'];
				$class = "bkng_short_input";
				$content = $bws_bkng->get_number_input( compact( 'name', 'value', 'class' ) ) . '<label class="bws_bkng_label">&nbsp;x&nbsp;</label>';

				$name  = "bkng_{$slug}_image_size[height]";
				$value = $size['height'];
				$class = "bkng_short_input";
				$content .= $bws_bkng->get_number_input( compact( 'name', 'value', 'class' ) ) . '<label class="bws_bkng_label">&nbsp;px</label>';

				$bws_bkng->display_table_row( $title, $content );
			}

			/**
			 * Crop image option
			 */
			$name  = 'bkng_crop_images';
			$attr  = $this->options['crop_images'] ? 'checked="checked"' : '';
			$attr .= 'data-affect-show=".bkng_show_if_crop_checked"';
			$class = 'bws_option_affect';

			$content  = $bws_bkng->get_checkbox( compact( 'name', 'attr', 'class' ) );
			$content .= $bws_bkng->get_info( __( 'Enable to crop images when loading. Disable to resize images automatically using their aspect ratio.', BWS_BKNG_TEXT_DOMAIN ) );
			$bws_bkng->display_table_row( __( 'Crop Images', BWS_BKNG_TEXT_DOMAIN ), $content );

			/**
			 * Crop image position option
			 */
			$y_crop_vector = array( 'top', 'center', 'bottom' );
			$x_crop_vector = array( 'left', 'center', 'right' );
			$values   = $this->options['crop_position'];
			$name     = 'bkng_crop_position';
			$selected = $this->options['crop_position']['vertical'] . '-' . $this->options["crop_position"]['horizontal'];
			$content  = '';
			foreach( $y_crop_vector as $y ) {
				foreach( $x_crop_vector as $x ) {
					$value = $y . '-' . $x;
					$attr  = $value == $selected ? 'checked="checked"' : '';
					$content .= $bws_bkng->get_radiobox( compact( 'name', 'value', 'attr' ) );
				}
				$content .= '<br />';
			}
			$content .= $bws_bkng->get_info( __( 'Select the base crop position (the default is center).', BWS_BKNG_TEXT_DOMAIN ) );
			$bws_bkng->display_table_row( __( 'Crop position', BWS_BKNG_TEXT_DOMAIN ), $content, 'bkng_show_if_crop_checked' );

			/**
			 * Use lightbox
			 */
			$name  = 'bkng_enable_lightbox';
			$attr  = $this->options['enable_lightbox'] ? 'checked="checked"' : '';

			$content = $bws_bkng->get_checkbox( compact( 'name', 'attr' ) );

			$bws_bkng->display_table_row( __( 'Enable Lightbox', BWS_BKNG_TEXT_DOMAIN ), $content ); ?>
		</table>
	<?php }

	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function tab_slider() {
		global $bws_bkng;
		$this->tab_title( __( 'Slider Settings', BWS_BKNG_TEXT_DOMAIN ) ); ?>
		<table class="form-table">
			<?php
			/**
			 * Use slider
			 */
			$name  = 'bkng_enable_slider';
			$attr  = $this->options['enable_slider'] ? 'checked="checked"' : '';

			$content = $bws_bkng->get_checkbox( compact( 'name', 'attr' ) );

			$bws_bkng->display_table_row( __( 'Enable Slider for Home Page', BWS_BKNG_TEXT_DOMAIN ), $content );

			/**
			 * Form in slider
			 */
			$name  = 'bkng_display_form';
			$value = array(
				array( 'value_id' => 'nope', 'value_name' => __( 'No', BWS_BKNG_TEXT_DOMAIN ) ),
				array( 'value_id' => 'center', 'value_name' => __( 'Centered', BWS_BKNG_TEXT_DOMAIN ) ),
				array( 'value_id' => 'left', 'value_name' => __( 'Align Left', BWS_BKNG_TEXT_DOMAIN ) ),
				array( 'value_id' => 'right', 'value_name' => __( 'Align Right', BWS_BKNG_TEXT_DOMAIN ) )
			);
			$current = empty( $this->options['display_form'] ) ? 'nope' : $this->options['display_form'];
			$content = $bws_bkng->get_radiobox( compact( 'name', 'value', 'current' ) );

			$bws_bkng->display_table_row( __( 'Add Search Form', BWS_BKNG_TEXT_DOMAIN ), $content );

			/**
			 * Post type in slider
			 */
			$name  = 'bkng_display_in_slider';
			$checked   = array();
			$args = array(
				'public'   => true,
				'_builtin' => false
			);
			$post_types = get_post_types( $args, 'objects' );
			$value = array();
			foreach( $post_types as $post_type ){
				$value[] = array( 'value_id' => $post_type->name, 'value_name' => $post_type->label );
			}
			$current_choice = empty( $this->options['display_in_slider'] ) ? array() : $this->options['display_in_slider'];
			foreach( $current_choice as $check ) {
				$checked[]['post_value'] = $check;
			}
			$content = $bws_bkng->get_checkbox( compact( 'name', 'value', 'checked' ) );

			$bws_bkng->display_table_row( __( 'Post Type in Slider', BWS_BKNG_TEXT_DOMAIN ), $content ); ?>
		</table>
	<?php }

	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function tab_advanced() {
		global $bws_bkng;
		$this->tab_title( __( 'Google Map Options', BWS_BKNG_TEXT_DOMAIN ) ); ?>
		<table class="form-table">
			<?php if( true === $this->is_general_settings ) {
				?>
				<div class="bws_tab_sub_label fcbkbttn_general_enabled"><?php _e( 'General', 'facebook-button-pro' ); ?></div>
				<?php
				$bws_bkng->display_table_row( __( 'Google Map Options', BWS_BKNG_TEXT_DOMAIN ), '' );
				/**
				 * Google map key option
				 */
				$name  = 'bkng_google_map_key';
				$value = $this->options['google_map_key'];

				$content = $bws_bkng->get_text_input( compact( 'name', 'value' ) );

				$bws_bkng->display_table_row( __( 'Key', BWS_BKNG_TEXT_DOMAIN ), $content );

				/**
				 * Google map language option
				 */
				$name  = 'bkng_google_map_auto_detect';
				$value = 1;
				$after = __( 'Auto (using WordPress locale)', BWS_BKNG_TEXT_DOMAIN );
				$attr  = $this->options['google_map_auto_detect'] ? ' checked="checked"' : '';
				$auto_detect_radiobox = $bws_bkng->get_radiobox( compact( 'name', 'value', 'attr', 'after' ) );

				$name  = 'bkng_google_map_auto_detect';
				$value = 0;
				$attr  = ! $this->options['google_map_auto_detect'] ? ' checked="checked"' : '';
				$manual_detect_radiobox = $bws_bkng->get_radiobox( compact( 'name', 'value', 'attr' ) );

				$name     = 'bkng_google_map_language';
				$selected = $this->options['google_map_language'];
				$options  = $bws_bkng->data_loader->load( 'map_languages' );

				$language_list = $bws_bkng->get_select( compact( 'name', 'options', 'selected' ) );

				$content = "<p>{$auto_detect_radiobox}</p><p>{$manual_detect_radiobox}{$language_list}</p>";

				$bws_bkng->display_table_row( __( 'Language', BWS_BKNG_TEXT_DOMAIN ), $content );

				/**
				 * Google map region option
				 */
				$name      = 'bkng_google_map_region';
				$value     = $this->options['google_map_region'];
				$maxlength = 10;

				$bws_info_text = sprintf(
					__( 'For more info see %s. To allow automatic region detection, just leave it empty', BWS_BKNG_TEXT_DOMAIN ) . '.',
					$bws_bkng->get_link( array(
						'href'   => 'https://developers.google.com/maps/documentation/javascript/localization#Region',
						'text'   => __( 'Region localization', BWS_BKNG_TEXT_DOMAIN ),
						'target' => true
					) )
				);

				$content = $bws_bkng->get_text_input( compact( 'name', 'value', 'maxlength' ) ) . '<div class="bws_info">' . $bws_info_text . '</div>';

				$bws_bkng->display_table_row( __( 'Region Identifier', BWS_BKNG_TEXT_DOMAIN ), $content );

				/**
				 * Google map default address option
				 */
				$class   = "bkng_address_input";
				$name    = "bkng_google_map_default_address";
				$value   = $this->options['google_map_default_address'];
				$address_input = $bws_bkng->get_text_input( compact( 'class', 'name', 'value' ) );

				$unit  = 'button';
				$class = "button bkng_find_by_address_button";
				$value = __( 'Find by address', BWS_BKNG_TEXT_DOMAIN );
				$find_by_address_button = $bws_bkng->get_form_unit( compact( 'unit', 'class', 'value' ) );

				$address_errors = $bws_bkng->get_errors( '', '', "inline bkng_js_errors bkng_find_by_address_error " . BWS_BKNG::$hidden );
				$content = $address_input . $find_by_address_button . $address_errors;

				$bws_bkng->display_table_row( __( 'Default Address', BWS_BKNG_TEXT_DOMAIN ), $content, 'bkng_map_extra_options ' ); // . BWS_BKNG::$hidden );

				/**
				 * Google map wrapper
				 */
				$content = '<div id="bkng_map_wrap" class="bkng_map_wrap"></div>';

				$bws_bkng->display_table_row( '', $content, 'bkng_map_extra_options ' ); // . BWS_BKNG::$hidden );

				/**
				 * Google map coordinates options
				 */
				$coors_errors = $bws_bkng->get_errors( '', '', 'inline bkng_js_errors bkng_find_by_coors_error ' . BWS_BKNG::$hidden );

				$unit  = 'button';
				$class = "button bkng_find_by_coordinates_button";
				$value = __( 'Find by coordinates', BWS_BKNG_TEXT_DOMAIN );
				$find_by_coors_button = $bws_bkng->get_form_unit( compact( 'unit', 'class', 'value' ) );

				$text_fields_names = array(
					'latitude'  => array( __( 'Latitude', BWS_BKNG_TEXT_DOMAIN ), $this->options['google_map_default_lat'] ),
					'longitude' => array( __( 'Longitude', BWS_BKNG_TEXT_DOMAIN ), $this->options['google_map_default_lng'] )
				);
                $latitude = $longitude = '';
				foreach ( $text_fields_names as $field_name => $data ) {
					$after  = $data[0];
					$class  = "bkng_{$field_name}_input";
					$name   = "bkng_google_map_default_{$field_name}";
					$value  = $data[1];
					${$field_name} = $bws_bkng->get_text_input( compact( 'after', 'class', 'name', 'value' ) );
				}

				$content = "{$coors_errors}<p>{$find_by_coors_button}</p><p>{$latitude}</p><p>{$longitude}</p>";

				$bws_bkng->display_table_row( __( 'Default Coordinates', BWS_BKNG_TEXT_DOMAIN ), $content, 'bkng_map_extra_options ' ); //" . BWS_BKNG::$hidden );
			} else {

				$data = array(
					'products'   => __( 'Remove all products', BWS_BKNG_TEXT_DOMAIN ),
					'attributes' => __( 'Remove all attributes', BWS_BKNG_TEXT_DOMAIN ),
					'orders'     => __( 'Remove all orders', BWS_BKNG_TEXT_DOMAIN )
				);

				$content = '';
				foreach( $data as $key => $after ) {
					$name = "bkng_remove_{$key}";
					$attr = empty( $this->options["remove_{$key}"] ) ? '' : 'checked="checked"';
					$content .= '<p>' . $bws_bkng->get_checkbox( compact( 'name', 'attr', 'after' ) ) . '</p>';
				}
				$bws_bkng->display_table_row( __( 'Uninstall Settings', BWS_BKNG_TEXT_DOMAIN ), $content );
			} ?>
		</table>
	<?php }


	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function tab_cart() {
		$class = 'BWS_BKNG_Settings_Cart';
		$page  = new $class();
		$page->display_tab_content();

	}

	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function tab_checkout() {
		$class = 'BWS_BKNG_Settings_Checkout';
		$page  = new $class();
		$page->display_tab_content();
	}

	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function tab_customer_notifications() {
		$class = 'BWS_BKNG_Settings_Emails';
		$page  = new $class();
		$page->display_tab_user_emails();
	}

	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function tab_agent_notifications() {
		$class = 'BWS_BKNG_Settings_Emails';
		$page  = new $class();
		$page->display_tab_admin_emails();
	}

	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function tab_bike_rental() {
		$class = 'BWS_BKNG_Settings_Products';
		$page  = new $class();
		$this->tab_title( __( 'Bikes', BWS_BKNG_TEXT_DOMAIN ) );
		$page->display_tab_content( 'bws_bike' );
	}

	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function tab_extras() {
		$class = 'BWS_BKNG_Settings_Products';
		$page  = new $class();
		$this->tab_title( __( 'Extras', BWS_BKNG_TEXT_DOMAIN ) );
		$page->display_tab_content( 'bws_extra' );
	}

	public function tab_hotel_booking() {
        $class = 'BWS_BKNG_Settings_Products';
        $page  = new $class();
        $this->tab_title( __( 'Hotels', BWS_BKNG_TEXT_DOMAIN ) );
        $page->display_tab_content( 'bws_hotel' );
    }

	public function tab_rooms() {
        $class = 'BWS_BKNG_Settings_Products';
        $page  = new $class();
        $this->tab_title( __( 'Rooms', BWS_BKNG_TEXT_DOMAIN ) );
        $page->display_tab_content( 'bws_room' );
    }

}
