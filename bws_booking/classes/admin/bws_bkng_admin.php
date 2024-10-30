<?php
/**
 * Contains functions which are used in site dashboard
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Admin' ) )
	return;

class BWS_BKNG_Admin {

	/**
	 * Class constructor
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function __construct() {

		add_action( 'admin_init', array( $this, 'admin_init' ) );

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_filter( 'set-screen-option', array( $this, 'save_screen_options' ), 10, 3 );

		if ( $this->is_booking_page() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ), 11 );
			add_action( 'admin_head', array( $this, 'add_noscript_styles' ) );
		}
	}

	/**
	 * Add necessary data for bws_menu
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function admin_init() {
		global $bws_plugin_info, $bws_bkng, $pagenow;

		if ( empty( $bws_plugin_info ) ) {
			$info = $bws_bkng->get_plugin_info();
			$bws_plugin_info = array( 'id' => $bws_bkng->link_pn, 'version' => $info['Version'] );
		}

		if ( 'plugins.php' == $pagenow ) {
			if ( empty( $info ) )
				$info = $bws_bkng->get_plugin_info();
			if ( $bws_bkng->is_pro ) {
				if ( function_exists( 'bws_add_plugin_banner_timeout' ) ) {
					bws_add_plugin_banner_timeout( $bws_bkng->plugin_basename, $bws_bkng->plugin_prefix, $info['Name'], $bws_bkng->wp_slug );
				}
			} else {
				if ( function_exists( 'bws_plugin_banner_go_pro' ) ) {
					$options = get_option( "{$bws_bkng->plugin_prefix}_options" );

					bws_plugin_banner_go_pro( $options, $info, $bws_bkng->plugin_prefix, $bws_bkng->plugin_prefix, $bws_bkng->link_key, $bws_bkng->link_pn, $bws_bkng->wp_slug );
				}
			}
		}
	}

	/**
	 * Adds plugin settings links to the dashboard menu
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function add_admin_menu() {
		global $bws_bkng, $submenu, $wp_version, $bws_post_type, $bws_admin_menu_pages, $_parent_pages, $bws_custom_columns;

		if( empty( $bws_admin_menu_pages ) ) {
			return;
		}
		$bws_admin_menu_pages = apply_filters( 'bws_bkng_admin_pages', $bws_admin_menu_pages );

		$orders_class = isset( $_GET['action'] ) && 'edit' == $_GET['action'] && ! empty( $_GET['bkng_order_id'] ) ? 'BWS_BKNG_Single_Order_Page' : 'BWS_BKNG_Orders_Page';
		$all_pages_callback = array(
			'orders_page'			=> $orders_class::get_instance( true ),
			'attributes_page'		=> BWS_BKNG_Attributes_Page::get_instance( true ),
			'locations_page'		=> BWS_BKNG_Locations_Page::get_instance( true ),
//			'settings_page'			=> BWS_BKNG_Settings_Page::get_instance( true ),
			'general_settings_page' => BWS_BKNG_Settings_Page::get_instance( true ),
			'user_page' 			=> new BWS_BKNG_Profile_Page(),
		);

		foreach( $bws_admin_menu_pages as $post_type => $pages  ) {
			foreach ( $pages as $key => $data ) {
				$page = add_submenu_page(
					str_replace( '%POST_TYPE%', $post_type, $data['parent_page'] ),
					$data['title'],
					$data['title'],
					$data['capability'],
					str_replace( array( '%PREFIX%', '%POST_TYPE%' ), array( $bws_bkng->plugin_prefix, $post_type ), $data['slug'] ),
					array_key_exists( $key, $all_pages_callback ) ? array( $all_pages_callback[ $key ],  $data['callback'] ) : $data['callback']
				);
				if ( 'bws_panel' != $key )
				add_action( "load-{$page}", array( $this, 'add_help_tabs' ) );

				if( ! empty( $data['load'] ) ){
					add_action( str_replace( '%PAGE%', $page, $data['load']['hook'] ), array( new $data['load']['function'][0](), $data['load']['function'][1] ) );
				}
			}
		}

		foreach ( $bws_post_type as $post_type => $post_data ) {
			if ( ! $bws_bkng->is_pro && isset( $submenu[ 'edit.php?post_type=' . $post_type ] ) ) {
				$info = $bws_bkng->get_plugin_info();
				$submenu[ 'edit.php?post_type=' . $post_type ][] = array(
					'<span style="color:#d86463"> ' . __( 'Upgrade to Pro', BWS_BKNG_TEXT_DOMAIN ) . '</span>',
					'manage_options',
					'https://bestwebsoft.com/products/wordpress/plugins/' . $bws_bkng->wp_slug . '/?k=' . $bws_bkng->link_key . '&pn=' . $bws_bkng->link_pn . '&v='. $info['Version'] . '&wp_v=' . $wp_version
				);
			}
			add_filter( 'manage_' . $post_type . '_posts_columns' , array( $this, 'add_custom_columns' ) );
			add_action( 'manage_' . $post_type . '_posts_custom_column' , array( $this, 'custom_columns_content' ), 10, 2 );
		}
		if ( ! empty( $bws_custom_columns ) ) {
			foreach( $bws_custom_columns as $post_type => $callback_info ) {
				foreach( $callback_info as $callback ) {
					add_filter( 'manage_' . $post_type . '_posts_columns' , $callback['callback_column'] );
					add_action( 'manage_' . $post_type . '_posts_custom_column' , $callback['callback_content'], 10, 2 );
				}
			}
		}
	}

	public function add_custom_columns( $columns ) {
		$thumb_column = array( 'featured-image' => __( 'Featured Image', BWS_BKNG_TEXT_DOMAIN ) );
		$cb_column    = array_slice( $columns, 0, 1 );
		$rest         = array_slice( $columns, 1 );

		return $cb_column + $thumb_column + $rest;
	}

	public function custom_columns_content( $column, $post_id ) {
		global $bws_bkng;
		switch ( $column ) {
			case 'featured-image':
				$thumb = get_the_post_thumbnail( $post_id, array( 65, 65 ) );
				echo empty( $thumb ) ? '<img width="65" src="' . esc_url( $bws_bkng->get_default_image_src() ) . '" />' : $thumb;
				break;
		}
	}


	/**
	 * Adds the plugin scripts and styles
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function add_scripts() {
		global $bws_bkng, $pagenow, $plugin_page;
		$url    = BWS_BKNG_URL;
		$locale = explode( "_", get_locale() );
		$screen = get_current_screen();
		$data   = array(
			'post_type' => $bws_bkng->get_post_types(),
			'prefix'    => 'bkng',
			'locale'    => $locale[0],
			'hidden'    => BWS_BKNG::$hidden,
			'show_extras_text'  => __( 'Show extras', BWS_BKNG_TEXT_DOMAIN ),
			'hide_extras_text'  => __( 'Hide extras', BWS_BKNG_TEXT_DOMAIN ),
			'nonce'             => wp_create_nonce( 'bkng_ajax_nonce' ),
			'time_format'       => get_option( 'time_format' ),
			'date_format'       => ( ( NULL !==  $bws_bkng->get_option( 'date_format' ) ) ? $bws_bkng->get_option( 'date_format' ) : 'Y/m/d' ),
			'dec_sep'           => $bws_bkng->get_option( 'number_decimal_separator' ),
			'thou_sep'          => $bws_bkng->get_option( 'number_thousand_separator' ),
			'default_lng'       => $bws_bkng->get_option( 'google_map_default_lng' ),
			'default_lat'       => $bws_bkng->get_option( 'google_map_default_lat' ),
			'default_addr'      => $bws_bkng->get_option( 'google_map_default_address' ),
			'default_work_from' => date( get_option( 'time_format' ), 3600 * 9 ),
			'default_work_till' => date( get_option( 'time_format' ), 3600 * 17 ),
			'locale'            => preg_replace( '/^([^-_]*)(.*?)$/','$1', get_locale() ),
			'save_attribute_confirm_message' => __( "Are you sure you want to do it? Attribute \"%s\" will be completely removed from the products data without the possibility to recover them", BWS_BKNG_TEXT_DOMAIN ) . '.',
			'delete_order_confirm_message' => __( "Are you sure you want to delete chosen orders", BWS_BKNG_TEXT_DOMAIN ) . '?'
		);

		wp_enqueue_style( 'bkng_admin_styles', "{$url}css/admin_style.css" );

		wp_register_script( 'bkng_general_script', "{$url}js/general.js" );
		wp_enqueue_script( 'bkng_admin_script', "{$url}js/admin_script.js", array( 'jquery', 'jquery-ui-tabs', 'jquery-ui-sortable', 'bkng_general_script' ) );

		wp_localize_script( 'bkng_admin_script', 'bws_bkng', $data );

		/* post type where we want to add class => post type from where class is removed */
		$post_types_to_switch_classes = [
		    'bws_extra' => 'bws_bike',
            'bws_room'  => 'bws_hotel',
        ];
        if (
            isset( $_REQUEST['post_type'] ) &&
            array_key_exists( $_REQUEST['post_type'], $post_types_to_switch_classes ) &&
            $plugin_page === 'bkng_general_settings'
        ) {
            $destination_post_type = sanitize_text_field( stripslashes( $_REQUEST['post_type'] ) );
            $remove_post_type = $post_types_to_switch_classes[ $destination_post_type ];
            $script = "
                'use strict';
                ( function( $ ) {
                    $( document ).ready( function() {
                        const $remove_post_type = $( '#menu-posts-$remove_post_type' );
                        const $destination_post_type = $( '#menu-posts-$destination_post_type' );
                        $remove_post_type.removeClass( 'wp-has-current-submenu wp-menu-open' ).addClass( 'wp-not-current-submenu' );
                        $remove_post_type.children( 'a' ).removeClass( 'wp-has-current-submenu wp-menu-open' ).addClass( 'wp-not-current-submenu' );
                        $destination_post_type.removeClass( 'wp-not-current-submenu' ).addClass( 'wp-has-current-submenu wp-menu-open' );
                        $destination_post_type.children( 'a' ).removeClass( 'wp-not-current-submenu' ).addClass( 'wp-has-current-submenu wp-menu-open' );
                    } )
                } )(jQuery)
            ";
            wp_add_inline_script(
                'bkng_admin_script',
                $script
            );
        }

		if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) || ( isset( $_GET['page'] ) && 'bkng_general_settings' == $_GET['page'] ) || ( isset( $_GET['page'] ) && strpos( $_GET['page'], '_locations' ) ) ) {
			$bws_bkng->add_google_map_scripts();
			BWS_BKNG_Products_Tree::enque_scripts();
			BWS_BKNG_Image_Gallery::enque_scripts();
		}
		bws_enqueue_settings_scripts();
		bws_plugins_include_codemirror();
	}

	/**
	 * Adds some styles in case if JS is disabled
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function add_noscript_styles() { ?>
		<noscript>
			<style type="text/css">
				.bkng_meta_input_wrap {
					padding: 0 !important;
					height: auto !important;
					border: none !important;
					box-shadow: none !important;
					-webkit-box-shadow: none !important;
				}
				.bkng_terms_count,
				.bkng_placeholder {
					display: none;
				}
				.bkng_meta_list{
					margin-top: 0;
				}
			</style>
		</noscript>
	<?php }

	public function is_settings_page( $action = '' ) {
		global $bws_bkng;
		$is_settings_page = isset( $_GET['page'] ) && $bws_bkng->settings_page_slug == $_GET['page'];
		return empty( $action ) ? $is_settings_page : $is_settings_page && isset( $_GET['action'] ) && $action == $_GET['action'];
	}

	/**
	 * Adds plugin scripts and styles
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return boolean
	 */
	public function is_booking_page() {
		global $hook_suffix, $current_screen, $bws_bkng;

		if ( is_object( $current_screen ) ) {
			$current_post_type = $current_screen->post_type;
		} elseif ( isset( $_GET['post_type'] ) ) {
			$current_post_type = sanitize_text_field( stripslashes( $_GET['post_type'] ) );
		} elseif ( isset( $_GET['post'] ) ) {
			$current_post_type = get_post_type( sanitize_text_field( stripslashes( $_GET['post'] ) ) );
		} else {
			$current_post_type = '';
		}
		$bws_post_types = $bws_bkng->get_post_types();
		return in_array( $current_post_type, $bws_post_types) || $this->is_settings_page();
	}

	/**
	 * Adds help tab to the plugin settings page
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function add_help_tabs() {
		$screen = get_current_screen();

		if ( $this->is_settings_page( 'emails' ) ) {
			$screen->add_help_tab( array(
				'id'      => 'bkng_mail_shortcodes',
				'title'   => __( 'Availble shortcodes', BWS_BKNG_TEXT_DOMAIN ),
				'content' => $this->show_mail_shortcodes()
			) );
		}

		$args = array(
			'id'      => 'bkng',
			'section' => '200538879'
		);
		bws_help_tab( $screen, $args );
	}

	/**
	 * Fetch the help tab HTML-content for "Notifications" tab on the plugin settings page
	 * @see self::add_help_tabs()
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return string
	 */
	public function show_mail_shortcodes() {
		$items = '';
		foreach( BWS_BKNG_Mailer::get_shortcodes() as $shortcode => $data ) {
			$items .= "<li><strong>{{$shortcode}}</strong>" . ( empty( $data['description'] ) ? '': "&nbsp;-&nbsp;{$data['description']}" ) . '</li>';
		}
		return '<h3>' . __( 'Availble shortcodes', BWS_BKNG_TEXT_DOMAIN ) . "</h3><ul>{$items}</ul>";
	}

	/**
	 * Adds screen options tabs to the plugin settings page
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function add_screen_options_tabs() {
		$args = array(
			'label'   => __( 'Items per Page', BWS_BKNG_TEXT_DOMAIN ),
			'default' => 20,
			'option'  => 'bkng_per_page',
		);
		add_screen_option( 'per_page', $args );
	}

	/**
	 * Saves the plugin pages' screen option
	 * @since  0.1
	 * @access public
	 * @param  bool|int $value  Screen option value. Default false to skip.
	 * @param  string   $option The option name.
	 * @param  int      $value  The number of rows to use.
	 * @return bool|int
	 */
	public function save_screen_options( $status, $option, $value ) {
		return 'bkng_per_page' == $option ? absint( $value ) : $status;
	}
}
