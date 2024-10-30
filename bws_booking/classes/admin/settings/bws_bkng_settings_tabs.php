<?php
/**
 * Inits Booking's core functioanlity
 * @since    Booking v0.2
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Settings_Tabs' ) )
	return;

if ( ! class_exists( 'Bws_Settings_Tabs' ) ) {
	global $bws_bkng;
	require_once( dirname( $bws_bkng->plugin_file ) . '/bws_menu/class-bws-settings.php' );
}

abstract class BWS_BKNG_Settings_Tabs extends Bws_Settings_Tabs {

	/**
	 * Contains the list of the page tabs settings
	 * @var    array
	 * @since  0.1
	 * @access protected
	 */
	protected $tabs;

	/**
	 * Contains the codes and messages that occured during handling requests
	 * @var    object    An instance of the class WP_Error
	 * @since  0.1
	 * @access protected
	 */
	protected $errors;

	/**
	 * Contains the codes and messages that need to be displaying after handling requests
	 * @var    object    An instance of the class WP_Error
	 * @since  0.1
	 * @access protected
	 */
	protected $notices;

	/**
	 * Class constructor
	 * Can be called only from within the child class.
	 * @access public
	 * @since  0.1
	 * @param  void
	 * @return void
	 */
	public function __construct() {
		global $bws_bkng, $wp_version, $wp_filter;
		parent::__construct( array(
			'plugin_basename'		=> plugin_basename( $bws_bkng->plugin_file ),
			'plugins_info'			=> $bws_bkng->get_plugin_info(),
			'prefix'				=> $bws_bkng->plugin_prefix,
			'default_options'		=> $bws_bkng->data_loader->load_default_options(),
			'options'				=> $bws_bkng->get_option(),
			'tabs'					=> $this->tabs,
			'wp_slug'				=> $bws_bkng->wp_slug,
			'demo_data'				=> '',
			'link_key'				=> $bws_bkng->link_key,
			'link_pn'				=> $bws_bkng->link_pn
		) );
		$hook_prefix =  get_parent_class( __CLASS__ );
        if ( ! isset( $wp_filter[ "{$hook_prefix}_additional_import_export_options" ] ) ) {
            add_action("{$hook_prefix}_additional_import_export_options", array($this, 'add_demo_controls'));
            add_action("{$hook_prefix}_additional_restore_options", array($this, 'rewrite_notifications'));
        }
	}

	/**
	 * Fetch the list of registered tabs.
	 * @access public
	 * @since  0.1
	 * @param  void
	 * @return array
	 */
	public function get_tabs() {
		return $this->tabs;
	}

	/**
	 * Saves the plugin options
	 * @access public
	 * @since  0.1
	 * @param  void
	 * @return array     The list of notification messages
	 */
	public function save_options() {
		global $bws_bkng;
		$this->prepare_options();

		if( isset( $_GET['page'] ) && 'bkng_general_settings' == $_GET['page'] ) {
            $bws_bkng->update_option( $this->options );
		} elseif ( isset( $_GET['post_type'] ) ) {
			$bws_bkng->update_post_type_option( sanitize_text_field( stripslashes( $_GET['post_type'] ) ), $this->options );
		}
		$notice  = $this->get_message( 'notices' );
		$error   = $this->get_message( 'errors' );
		$message = empty( $error ) ? __( 'Settings saved', BWS_BKNG_TEXT_DOMAIN ) : '';

		return compact( 'message', 'notice', 'error' );
	}

	/**
	 * Restore default notifications during restoring the plugin options
	 * @access public
	 * @since  0.1
	 * @param  array    The plugin options
	 * @return array    The plugin options
	 */
	public function rewrite_notifications( $options ) {
		global $bws_bkng, $wpdb;

		$table = BWS_BKNG_DB_PREFIX . sanitize_text_field( stripslashes( $_GET['post_type'] ) ) . '_notifications';
		$wpdb->query( "TRUNCATE `{$table}`;" );
		$bws_bkng->data_loader->create_db_tables();
		return $options;
	}


	/**
	 * Prepares plugin options before further saving to the database
	 * @abstract
	 * @access protected
	 * @since  0.1
	 * @param  void
	 * @return void
	 */
	abstract protected function prepare_options();

	/**
	 * Displays the current tab title
	 * @access protected
	 * @since  0.1
	 * @param  string    $title
	 * @return void
	 */
	protected function tab_title( $title ) { ?>
		<h3 class="bws_tab_label"><?php echo esc_html( $title ); ?></h3>
		<?php $this->help_phrase(); ?>
		<hr />
	<?php }

	/**
	 * Adds the notification message to the appropriate storage for the further displaying
	 * @access protected
	 * @since  0.1
	 * @param  string    $code          The message code
	 * @param  string    $message       The message text
	 * @param  string    $message_type  The message type  ( 'notices' or 'errors' )
	 * @return void
	 */
	protected function add_message( $code, $message, $message_type = 'errors' ) {

		if ( ! $this->$message_type instanceof WP_Error )
			$this->$message_type = new WP_Error();

		$this->$message_type->add( $code, $message );
	}

	/**
	 * Fetch the message befoore displaying it
	 * @access protected
	 * @since  0.1
	 * @param  string    $message_type  The message type  ( 'notices' or 'errors' )
	 * @return string                   The message content
	 */
	protected function get_message( $message_type = 'errors' ) {

		if ( ! $this->$message_type instanceof WP_Error )
			return false;

		$codes = $this->$message_type->get_error_codes();

		if ( empty( $codes ) )
			return false;

		$messages = array();

		foreach( $codes as $code )
			$messages[ $code ] = $this->$message_type->get_error_message( $code );

		return implode( '<br />', $messages );
	}


    /**
     * Displays the "Add/Remove Demo" button on General -> Import/Export tab
     * @access public
     * @since  0.1
     * @param  void
     * @return void
     */
    public function add_demo_controls() {
        global $bws_bkng, $post_type;
        $demo = BWS_BKNG_Demo_Data_Loader::get_instance(); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e( 'Demo Data', BWS_BKNG_TEXT_DOMAIN ); ?></th>
                <td>
                    <?php if ( $demo->is_demo_installed() ) {
                        $confirm   = 'remove_demo';
                        $title     = __( 'Remove Demo Data', BWS_BKNG_TEXT_DOMAIN );
                        $form_info = __( 'Not recommended in case you have changed and use demo data (products, attributes, etc.).', BWS_BKNG_TEXT_DOMAIN );
                    } else {
                        $confirm   = 'install_demo';
                        $title     = __( 'Install Demo Data', BWS_BKNG_TEXT_DOMAIN );
                        $form_info = __( 'Install demo data to add demo products and demo extras with images, details, manufacturers, body types, and others.', BWS_BKNG_TEXT_DOMAIN );
                    }
                    $post_type = sanitize_text_field( stripslashes( $_REQUEST['post_type'] ) ); ?>
                    <p>
                        <a class="button" href="<?php echo esc_url( admin_url( "edit.php?post_type={$post_type}&page={$bws_bkng->settings_page_slug}&bkng_confirm={$confirm}" ) ); ?>">
                            <?php echo esc_html( $title ); ?>
                        </a>
                    </p>
                    <div class="bws_info"><?php echo esc_html( $form_info ); ?></div>
                </td>
            </tr>
        </table>
    <?php }

    /**
	 * Some magic to avoid the creation of several instances of this class
	 * @since  0.1
	 */
	private function __clone()  {}
	private function __sleep()  {}
	private function __wakeup() {}

}
