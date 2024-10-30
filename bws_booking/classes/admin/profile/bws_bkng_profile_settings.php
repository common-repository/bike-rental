<?php
/**
 * Handle the content of "Settings" tab of user profile
 * @since  Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Profile_Settings' ) )
	return;

class BWS_BKNG_Profile_Settings extends BWS_BKNG_Settings_Tabs {

	/**
	 * Contains class instance
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $user_class, $gallery_class;

	/**
	 * Class constructor
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function __construct() {
		$this->tabs = array(
			'settings' 	=> array( 'label' => __( 'Personal Info', BWS_BKNG_TEXT_DOMAIN ) ),
			'images' 	=> array( 'label' => __( 'Gallery', BWS_BKNG_TEXT_DOMAIN ) ),
		);

		add_action( 'Bws_Settings_Tabs_display_metabox', array( $this, 'avatar_metabox' ) );

		$this->user_class 		= BWS_BKNG_User::get_instance();
		$this->gallery_class 	= new BWS_BKNG_Image_Gallery( $this->user_class->gallery_id, 'bkng_user_gallery' );

		$this->user_class->enqueue( 'avatar' );
		$this->gallery_class->enque_scripts();

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
		if ( ! empty( $_POST['bkng_user_avatar'] ) || '0' === $_POST['bkng_user_avatar'] ) {
			update_user_option( $this->user_class->user_id, 'bkng_user_avatar', sanitize_text_field( stripslashes( $_POST['bkng_user_avatar'] ) ), true );
		}

        $bkng_user_settings = array_map( 'sanitize_text_field', $_POST['bkng_user_settings'] );
        if ( ! empty( $bkng_user_settings ) ) {
			$bkng_user_settings['bws_bkng_dob_consent'] = isset( $bkng_user_settings['bws_bkng_dob_consent'] ) ? 1 : 0;

			foreach ( $bkng_user_settings as $option => $value ) {
				update_user_option( $this->user_class->user_id, $option, $value, true );
			}
		}

        $password_data = array_map( 'trim', $_POST['bkng_user_password'] );
        if ( ! empty( $password_data['curr_pass'] ) ) {
			$response = $this->user_class->change_user_password( $password_data );

			switch ( $response ) {
				case 'wrong_pass_conf':
					$this->add_message( 'wrong_pass_conf', __( 'Your new password does not match.', BWS_BKNG_TEXT_DOMAIN ) );
					break;
				case 'wrong_pass':
					$this->add_message( 'wrong_pass', __( 'Invalid password.', BWS_BKNG_TEXT_DOMAIN ) );
					break;
				case 'pass_changed':
					$this->add_message( 'pass_changed', __( 'Your password has been successfully changed. Please, reload the page.', BWS_BKNG_TEXT_DOMAIN ) );
					break;
			}
		}

		if ( isset( $_POST['prflxtrflds_user_field_value'] ) ) {
			prflxtrflds_save_booking_fields( array() );
		}

		$this->gallery_class->save_images();
	}

	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function tab_images() {
		$this->tab_title( __( 'Your Gallery', BWS_BKNG_TEXT_DOMAIN ) );

		$fields = $this->user_class->get_user_settings_fields( 'gallery' );
		?>
		<table class="form-table">
			<?php $this->display_fields( $fields ); ?>
		</table>
		<?php
		echo $this->gallery_class->get_content( false, 'bkng_user_gallery_wrap' );
	}

	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function tab_settings() {
		$this->tab_title( __( 'Your Personal Info', BWS_BKNG_TEXT_DOMAIN ) );
		?>
		<table class="form-table">
			<?php
			$fields = $this->user_class->get_user_settings_fields( 'wp_settings' );

			$this->display_fields( $fields );
			?>
		</table>
		<?php
		$this->change_password_form();
		$this->dob_form();
		$this->billing_data_form();
		$this->get_pef_tables();
	}

	public function avatar_metabox() {
		?>
		<div class="meta-box-sortables ui-sortable">
			<div id="submitdiv" class="postbox">
				<h3 class="hndle"><?php _e( 'Profile Picture', 'bestwebsoft' ); ?></h3>
				<div class="inside">
					<div class="submitbox bws-bkng-avatar-wrap" id="submitpost">
						<div id="minor-publishing">
							<?php $this->user_class->get_avatar_img_field(); ?>
						</div>
						<div id="major-publishing-actions">
							<div id="delete-action">
								<?php $this->user_class->get_avatar_delete_btn(); ?>
							</div>
							<div id="publishing-action">
								<?php $this->user_class->get_avatar_upload_btn(); ?>
							</div>
							<div class="clear"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public function dob_form() {
		?>
		<div class="bws_tab_sub_label"><?php _e( 'Date of birth', BWS_BKNG_TEXT_DOMAIN ); ?></div>
		<table class="form-table">
			<?php
			$fields = $this->user_class->get_user_settings_fields( 'bkng_birthday' );
			$this->display_fields( $fields );
			?>
		</table>
		<?php
	}

	public function billing_data_form() {
		?>
		<div class="bws_tab_sub_label"><?php _e( 'Billing Data', BWS_BKNG_TEXT_DOMAIN ); ?></div>
		<table class="form-table">
			<?php
			$fields = $this->user_class->get_user_settings_fields( 'billing_data' );
			$this->display_fields( $fields );
			?>
		</table>
		<?php
	}

	public function change_password_form() {
		?>
		<div class="bws_tab_sub_label"><?php _e( 'Change Password', BWS_BKNG_TEXT_DOMAIN ); ?></div>
		<table class="form-table">
			<?php
			$fields = $this->user_class->get_user_settings_fields( 'password_form' );
			$this->display_fields( $fields );
			?>
		</table>
		<?php
	}

	public function get_pef_tables() {
		global $wp_current_filter;

		if ( ! function_exists( 'prflxtrflds_fields_table' ) ) {
			return;
		}

		$old_filter = $wp_current_filter;
		$wp_current_filter = array();

		ob_start();
		prflxtrflds_fields_table();
		$content = ob_get_clean();

		echo preg_replace( '/<h2 class="prflxtrflds_extra_fields_profile">(.*?)<\/h2>/', '<div class="bws_tab_sub_label">$1</div>', $content );

		$wp_current_filter = $old_filter;
	}

	public function display_fields( $fields ) {
		global $bws_bkng;

		$defaults = array(
			'id' 			=> '',
			'class' 		=> 'bkng_user_info',
			'name_group' 	=> 'bkng_user_settings',
			'label' 		=> '',
			'type' 			=> 'text',
			'attr' 			=> false,
			'required' 		=> false,
			'options' 		=> false,
			'selected' 		=> false,
			'placeholder' 	=> false,
			'helper' 		=> false,
			'default' 		=> false
		);

		foreach ( $fields as $field ) {
			$field = wp_parse_args( $field, $defaults );

			$field['name'] = $field['name_group'] . '[' . $field['id'] . ']';

			switch ( $field['id'] ) {
				case 'bws_bkng_location':
					$field['options'] = array( 'default' => ' ' ) + $field['options'];
				case 'user_email':
				case 'display_name':
					$user = get_userdata( $this->user_class->user_id );
					$object = $field['id'];
					$field['value'] = $user->$object;
					break;
				default:
					$field['value'] = get_user_option( $field['id'], $this->user_class->user_id );
					break;
			}

			if ( false === $field['value'] && false !== $field['default'] ) {
				$field['value'] = $field['default'];
			}

			if ( 'select' == $field['type'] ) {
				$field['selected'] = $field['value'];
			}

			if ( $field['required'] ) {
				$field['label'] .= ' <span class="description">*</span>';
				$field['attr'] .= ' required="required"';
			}

			if ( $field['placeholder'] ) {
				$field['attr'] .= ' placeholder="' . $field['placeholder'] . '"';
			}

			if ( $field['helper'] ) {
				$field['after'] = '<span class="bws_info">' . $field['helper'] . '</span>';
			}

			switch ( $field['type'] ) {
				case 'text':
					$content = $bws_bkng->get_text_input( $field );
					break;
				case 'textarea':
					$field['attr'] .= ' rows="5"';
				case 'button':
				case 'img':
					$field['unit'] = $field['type'];
					$content = $bws_bkng->get_form_unit( $field );
					break;
				case 'email':
				case 'password':
					$content =  $bws_bkng->get_input( $field );
					break;
				case 'select':
					$content =  $bws_bkng->get_select( $field );
					break;
				case 'number':
					$content =  $bws_bkng->get_number_input( $field );
					break;
				case 'checkbox':
					$field['attr'] .= checked( $field['value'], true, false );
					$content =  $bws_bkng->get_checkbox( $field );
					break;
			}

			$bws_bkng->display_table_row( $field['label'], $content );
		}
	}
}
