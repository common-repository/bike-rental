<?php
/**
 * Displays plugin settings page
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */


if ( ! defined( 'ABSPATH' ) )
    die();

if ( class_exists( 'BWS_BKNG_Settings_Page' ) )
	return;

class BWS_BKNG_Settings_Page {
	private static $instance = NULL;

	/**
	 * The list of page tabs and its settings
	 * @since  0.1
	 * @access private
	 * @var    array
	 */
	private $tabs = array();

	/**
	 * Contains the slug of the current tab
	 * @since  0.1
	 * @access private
	 * @var string
	 */
	private $current_tab = '';

	/**
	 * Contains the list of error messages
	 * @since  0.1
	 * @access private
	 * @var mixed
	 */
	private $errors;

	/**
	 * Contains the list of messages
	 * @since  0.1
	 * @access private
	 * @var array
	 */
	private $messages = array();

	/**
	 * Contains the flag for general setting page
	 * @since  0.1
	 * @var    bool
	 * @access private
	 */
	private $is_general_settings;

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

	/**
	 * Class constructor
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function __construct() {
		global $bws_bkng, $bws_slug_forms, $bws_general_subtabs;

		if( isset( $_GET['page'] ) && 'bkng_general_settings' == $_GET['page'] ) {
			$this->is_general_settings = true;
		} else {
			$this->is_general_settings = false;
			if( isset( $_GET['post_type'] ) ) {
				$bws_bkng->settings_page_slug = $bws_bkng->plugin_prefix . '_' . sanitize_text_field( stripslashes( $_GET['post_type'] ) ) . '_settings';
			}
		}

		$this->tabs = array(
			'general' => array(
				'label' => __( 'General', BWS_BKNG_TEXT_DOMAIN )
			),
        );
        $this->tabs = ! empty( $this->tabs ) ? array_merge( $this->tabs,  $bws_general_subtabs ) : $bws_general_subtabs;

		/**
		 * Redirect during transition from old version of plugin to current version
		 * @deprecated 1.0.8
		 * @todo Remove function after 01.06.2019
		 */
		if ( isset( $_POST['bkng_upgrade_core'] ) ) {
      		$bws_bkng->data_loader->create_db_tables();
			$default_options = $bws_bkng->data_loader->load( 'default_settings' );
            $post_type = sanitize_text_field( stripslashes( $_GET['post_type'] ) );
			$bws_bkng_upgrade_options = get_option( sanitize_text_field( stripslashes( $bws_slug_forms[ $post_type ]['pure_slug'] . '_options' ) ) );
			add_option( sanitize_text_field( stripslashes( $bws_slug_forms[ $post_type ]['pure_slug'] . '_upgrade_options' ) ), $bws_bkng_upgrade_options);

			update_option( sanitize_text_field( stripslashes( $bws_slug_forms[ $post_type ]['pure_slug'] . '_options' ) ), $default_options );
			wp_redirect( 'admin.php?page=' . esc_attr( $bws_slug_forms[ $post_type ]['slug'] . '-settings' ) );
			exit;
		}
		/**
		 * End deprecated
		 */

		$this->current_tab = isset( $_GET['action'] ) && in_array( $_GET['action'], array_keys( $this->tabs ) ) ? sanitize_text_field( stripslashes( $_GET['action'] ) ): 'general';

		$this->handle_actions();
	}

	/**
	 * Handle page actions
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return void
	 */
	private function handle_actions() {
		global $bws_bkng, $wpdb;
		if ( isset( $_POST['bkng_add_status'] ) && ! empty( $_POST['bkng_products_new_status']['slug'] ) ) {
			check_admin_referer( plugin_basename( $bws_bkng->plugin_file ), 'bws_nonce_name' );
			$statuses = $bws_bkng->get_option( 'products_statuses' );
			$new_slug = sanitize_title( $_POST['bkng_products_new_status']['slug'] );

			if ( ! empty( $new_slug ) && ! array_key_exists( $new_slug, array_keys( $statuses ) ) ) {
				$statuses[ $new_slug ] = array(
					'title' => sanitize_text_field( stripslashes( $_POST['bkng_products_new_status']['title'] ) )
				);
				$bws_bkng->update_option( $statuses, 'products_statuses' );
				$this->messages[] = __( 'New products status has been added successfully', BWS_BKNG_TEXT_DOMAIN );
			}
		} elseif ( isset( $_REQUEST['bkng_remove_status'] ) ) {
			check_admin_referer( BWS_BKNG_PATH, 'bkng_nonce_name' );
            $action = sanitize_text_field( stripslashes( $_GET['action'] ) );
            $statuses = $bws_bkng->get_option( $action );
			$to_remove = sanitize_title( $_REQUEST['bkng_remove_status'] );
			if ( ! empty( $to_remove ) && array_key_exists( $to_remove, $statuses[ 'products_statuses' ] ) ) {
            unset( $statuses[ 'products_statuses' ][ $to_remove ] );
                $bws_bkng->update_option( $statuses, $action );
				$wpdb->update(
					$wpdb->postmeta,
					array(
                        'meta_value' => sanitize_text_field( stripslashes( $_POST['bkng_mark_as_status'] ) )
                    ),
					array(
						'meta_key'   => 'bkng_product_status',
						'meta_value' => esc_sql( $to_remove )
					)
				);
				if ( ! empty( $_POST['action'] ) )
					wp_redirect( 'edit.php?post_type=' . $action . '&page=' . $bws_bkng->settings_page_slug .'&action=' . $action . '&message=status_removed' );
			}
		} elseif ( ! empty( $_POST['bkng_install_demo'] ) ) {
			check_admin_referer( BWS_BKNG_PATH, 'bkng_nonce_name' );
			$demo = BWS_BKNG_Demo_Data_Loader::get_instance();
			$demo->install();
			$this->errors = $demo->get_errors();
			if ( empty( $this->errors ) )
				wp_redirect( 'edit.php?post_type=' . sanitize_text_field( $_GET['post_type'] ) . '&page=' . $bws_bkng->settings_page_slug .'&action=' . sanitize_text_field( $_POST['action'] ) . '&message=demo_installed' );
		} elseif ( ! empty( $_POST['bkng_remove_demo'] ) ) {
			check_admin_referer( BWS_BKNG_PATH, 'bkng_nonce_name' );
			$demo = BWS_BKNG_Demo_Data_Loader::get_instance();
			$demo->remove();
			$this->errors = $demo->get_errors();
			if ( empty( $this->errors ) )
				wp_redirect( 'edit.php?post_type=' . sanitize_text_field( $_GET['post_type'] ) . '&page=' . $bws_bkng->settings_page_slug .'&action=' . sanitize_text_field( $_POST['action'] ) . '&message=demo_removed' );
		}
	}

	/**
	 * Displays plugin settings page
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function display() {
		global $bws_bkng, $bws_slug_forms, $bws_general_subtabs;

        $bwsSubtabsKeys = [];

		$class = 'BWS_BKNG_Settings_' . ucfirst( $this->current_tab );

        foreach ( $bws_general_subtabs as $key => $subtab ) {
             if( $key == strtolower( ucfirst( $this->current_tab ) ) ) {

                 $bwsSubtabsKeys[] = $key;
            }
         }

        if ( isset( $_GET['action']) && in_array($_GET['action'], $bwsSubtabsKeys ) && $_GET['action'] == strtolower( ucfirst( $this->current_tab ) ) )
            $class = 'BWS_BKNG_Settings_General';

        $page  = new $class();

		if ( method_exists( $page, 'add_request_feature' ) ) {
            $page->add_request_feature();
		} ?>
		<div class="wrap bkng_settings_page<?php echo 1 == count( $page->get_tabs() ) ? ' bkng_single_tab_page' : ''; ?>">
			<h1><?php echo esc_html( ( true === $this->is_general_settings ) ? $bws_bkng->general_settings_page_title : $bws_bkng->settings_page_title ) ; ?></h1>
			<?php
			/**
			 * Redirect during transition from old version of plugin to current version
			 * @deprecated 1.0.8
			 * @todo Remove function after 01.06.2019
			 */
			if ( isset( $_GET['flag'] ) && 'true' == $_GET['flag'] ) { ?>
				<div class="error inline">
					<p><?php _e( 'Ability to manage the plugin settings is not available any more. Please upgrade the plugin core and re-create all your products', BWS_BKNG_TEXT_DOMAIN ); ?></p>
					<form action="admin.php?page=<?php echo esc_attr( $bws_slug_forms[ $_GET['post_type'] ]['slug'] ); ?>-settings" method="post">
						<p><input type="submit" class="button button-primary" name="bkng_upgrade_core" value="<?php _e( 'Update the plugin core', BWS_BKNG_TEXT_DOMAIN ); ?>" /></p>
					</form>
				</div>
				<?php return;
			}
			/**
			 * End deprecated
			 */
			if ( empty( $_GET['bkng_confirm'] ) || ! empty( $this->errors ) ) { ?>
				<h2 class="nav-tab-wrapper"><?php $this->display_tabs(); ?></h2>
				<?php $this->display_notices();
                if ( isset( $_POST['bws_restore_confirm'] ) && check_admin_referer( $page->plugin_basename, 'bws_settings_nonce_name' ) ) {
                    $page->save_all_tabs_options();
                }
				$page->display_content();
			} else {
				$this->display_confirm_form();
			} ?>
		</div><!-- .wrap .bkng_settings_page -->
	<?php }

	/**
	 * Displays additional actions confirm form
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return void
	 */
	private function display_confirm_form() {
		global $bws_bkng;
		$back_link_tab = '';
		switch( $_GET['bkng_confirm'] ) {
			case 'remove_status':
                $statuses = $bws_bkng->get_option( sanitize_text_field( stripslashes( $_GET['action'] ) ) );
                $statuses = $statuses[ 'products_statuses' ];
				$options  = array();
				$name     = 'bkng_mark_as_status';
				$removed  = '';
				foreach( $statuses as $slug => $data ) {
					if ( $slug == $_GET['status'] )
						$removed = $data['title'];
					else
                        $options[ $slug ] = $slug;
				}

				$additional_content = '<p>' . $bws_bkng->get_select( compact( 'name', 'options' ) ) . '</p>';
				$message        = __( 'Select new status for products that are marked as', BWS_BKNG_TEXT_DOMAIN ) . ' ' . $removed;
				$back_link_tab  = 'products';
				$hidden_value   = sanitize_text_field( stripslashes( $_GET['status'] ) );
				break;
			case 'install_demo':
				$message        = __( 'New products will be created that you can manage the way you want', BWS_BKNG_TEXT_DOMAIN );
				$back_link_tab  = 'general';
				$hidden_value   = sanitize_text_field( stripslashes( $_GET['bkng_confirm'] ) );
				break;
			case 'remove_demo':
				$message        = __( 'Demo data will be completely removed from your site', BWS_BKNG_TEXT_DOMAIN );
				$back_link_tab  = 'general';
				$hidden_value   = sanitize_text_field( stripslashes( $_GET['bkng_confirm'] ) );
				break;
			default:
				break;
		} ?>
		<p><?php echo esc_html( $message); ?>.</p>
		<?php $bws_bkng->form_open();

		if ( ! empty( $additional_content ) )
			echo $additional_content; ?>

		<p>
			<?php if ( ! empty( $back_link_tab ) ) {
				$name  = "bkng_send_confirm";
				$value = __( 'Confirm' );
				$class = 'button button-primary';
				echo $bws_bkng->get_button_input( compact( 'name', 'class', 'value' ) );

				$name  = "bkng_" . sanitize_text_field( stripslashes( $_GET['bkng_confirm'] ) );
				$value = esc_attr( $hidden_value );
				echo $bws_bkng->get_hidden_input( compact( 'name', 'value' ) );

				$name  = "action";
				$value = esc_attr( $back_link_tab );
				echo $bws_bkng->get_hidden_input( compact( 'name', 'value' ) );
			}

			$class = 'button button-default';
			$href = admin_url( 'edit.php?post_type=' . sanitize_text_field( $_GET['post_type'] ) . '&amp;page=' . $bws_bkng->settings_page_slug . ( empty( $back_link_tab ) ? '' : '&amp;action=' . $back_link_tab ) );
			$text = __( 'Cancel', BWS_BKNG_TEXT_DOMAIN );
			echo $bws_bkng->get_link( compact( 'href', 'text', 'class' ) ); ?>
		</p>
		<?php $bws_bkng->form_close( false );
	}

	/**
	 * Displays the plugin setting horisontal tabs
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return void
	 */
	private function display_tabs() {

        $link = '<a class="nav-tab %1$s" href="' . esc_url( admin_url( 'edit.php?post_type=' . sanitize_text_field( stripslashes( $_GET['post_type'] ) ) . '&page=bkng_general_settings&amp;action=%2$s' ) ) . '">%3$s</a>';

		$tabs = '';
		foreach( $this->tabs as $slug => $data ) {
			$class = $this->current_tab == $slug ? 'nav-tab-active' : '';
			$tabs .= sprintf( $link, $class, $slug, $data['label'] );
		}
		echo $tabs;
	}

	/**
	 * Displays the plugin notices
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return void
	 */
	private function display_notices() {
		global $bws_bkng;

		if ( ! empty( $_GET['message'] ) ) {
			switch( $_GET['message'] ) {
				case 'status_removed':
					$this->messages[] =__( 'Status has been removed successfully', BWS_BKNG_TEXT_DOMAIN);
					break;
				case 'demo_installed':
					$this->messages[] = __( 'Demo data successfully installed', BWS_BKNG_TEXT_DOMAIN );
					break;
				case 'demo_removed':
					$this->messages[] = __( 'Demo data deleted successfully', BWS_BKNG_TEXT_DOMAIN );
					break;
				default:
					break;
			}
		}

		if ( ! empty( $this->errors ) )
			echo $bws_bkng->get_errors( $this->errors );

		if ( ! empty( $this->messages ) )
			echo $bws_bkng->get_messages( $this->messages );
	}
}
