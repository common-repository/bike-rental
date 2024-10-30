<?php
/**
 * Handle the content of "Emails" tab
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Settings_Emails' ) )
	return;

class BWS_BKNG_Settings_Emails extends BWS_BKNG_Settings_Tabs {

	/**
	 * The list of data used to manage the tab content
	 * @since   0.1
	 * @var     array
	 * @access  private
	 */
	private $orders;

	/**
	 * The list of users that allowed to recieve admin notifications
	 * @since   0.1
	 * @var     array
	 * @access  private
	 */
	private $agents_data;

	private $post_type;

	/**
	 * Class constructor
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function __construct() {
		global $bws_bkng;
		$this->tabs = array(
			'user_emails'  => array( 'label' => __( 'Customer Notifications', BWS_BKNG_TEXT_DOMAIN ) ),
			'admin_emails' => array( 'label' => __( 'Agent Notifications', BWS_BKNG_TEXT_DOMAIN ) ),
		);

		$this->orders = array(
			'to_customer' => array(
				'customer_on_hold_order'   => array( __( 'New Order', BWS_BKNG_TEXT_DOMAIN ), __( 'Enable to notify customers of their order being placed or marked as "On Hold"', BWS_BKNG_TEXT_DOMAIN ) ),
				'customer_processed_order' => array( __( 'Processed Order', BWS_BKNG_TEXT_DOMAIN ), __( 'Enable to notify customers of their order being processed', BWS_BKNG_TEXT_DOMAIN ) ),
				'customer_completed_order' => array( __( 'Completed Order', BWS_BKNG_TEXT_DOMAIN ), __( 'Enable to notify customers of their order being completed', BWS_BKNG_TEXT_DOMAIN ) ),
				'customer_canceled_order'  => array( __( 'Canceled Order', BWS_BKNG_TEXT_DOMAIN ),  __( 'Enable to notify customers of their order being canceled', BWS_BKNG_TEXT_DOMAIN ) )
			),
			'to_agent' => array(
				'agent_on_hold_order'   => array( __( 'New Order', BWS_BKNG_TEXT_DOMAIN ), __( 'Enable to notify agents of a new order being placed or marked as "On Hold"', BWS_BKNG_TEXT_DOMAIN ) ),
				'agent_processed_order' => array( __( 'Processed Order', BWS_BKNG_TEXT_DOMAIN ), __( 'Enable to notify agents of an order being marked as "Processed"', BWS_BKNG_TEXT_DOMAIN ) ),
				'agent_completed_order' => array( __( 'Completed Order', BWS_BKNG_TEXT_DOMAIN ), __( 'Enable to notify agents of an order being marked as "Completed"', BWS_BKNG_TEXT_DOMAIN ) ),
				'agent_canceled_order'  => array( __( 'Canceled Order', BWS_BKNG_TEXT_DOMAIN ),  __( 'Enable to notify agents of order cancellation', BWS_BKNG_TEXT_DOMAIN ) )
			)
		);

		$this->agents_data = $bws_bkng->get_agents( 'send_bws_bkng_notifications', array( 'user_email', 'user_nicename' ) );
		$this->post_type = sanitize_text_field( stripslashes( $_GET['post_type'] ) );

		parent::__construct();
	}

	/**
	 * Prepares the plugin options before further saving to database
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function prepare_options( $options = NULL ) {
		global $wpdb;
		$from_value    = array( 'agent', 'custom' );
		$notices_table = BWS_BKNG_DB_PREFIX . $this->post_type . '_notifications';

		if ( NULL !== $options ) {
			$this->options = $options;
		}

		/* strings */
		$this->options['from_name'] = sanitize_text_field( stripslashes( $_POST['bkng_from_name'] ) );

		/* variations */
		$this->options['from_value'] = in_array( $_POST['bkng_from_value'], $from_value ) ? sanitize_text_field( stripslashes( $_POST['bkng_from_value'] ) ) : $this->options['from_value'];

		/* emails */
		$emails_options = array( 'agent_sender_email', 'from_email' );
		foreach( $emails_options as $email_option ) {
			$email = sanitize_text_field( stripslashes( $_POST["bkng_{$email_option}"] ) );
			$this->options[ $email_option ] = ! empty( $email ) && is_email( $email ) ? $email : $this->options[ $email_option ];
		}

		/* emails arrays */
		$recipient_emails = array(
			'agent_recipient_emails'      => ( empty( $_POST['bkng_agent_recipient_emails'] ) ? '' : array_map( 'sanitize_email', $_POST['bkng_agent_recipient_emails'] ) ),
			'additional_recipient_emails' => preg_split( "/[\s,;]+/", trim( $_POST['bkng_additional_recipient_emails'], " \s\r\n\t,;" ), -1, PREG_SPLIT_NO_EMPTY )
		);

		if ( ! empty( $recipient_emails ) ) {
			foreach( $recipient_emails as $email_option => $emails ) {
				$new_emails = array_filter( array_filter( ( array )$emails ), 'is_email' );
				$this->options[ $email_option ] = $emails;
			}
		}

		$notice_types = array_merge( array_keys( $this->orders['to_customer'] ), array_keys( $this->orders['to_agent'] ) );
		$values       = array();

		foreach ( $notice_types as $type ) {
		    if ( empty( $_POST['bkng_notice'][ $type ]['subject'] ) ) {
		        continue;
            }
			$subject  = empty( $_POST['bkng_notice'][ $type ]['subject'] ) ? '' : sanitize_text_field( stripslashes( $_POST['bkng_notice'][ $type ]['subject'] ) );
			$body     = empty( $_POST['bkng_notice'][ $type ]['body'] )    ? '' : sanitize_text_field( stripslashes( $_POST['bkng_notice'][ $type ]['body'] ) );
			$enabled  = empty( $_POST['bkng_notice'][ $type ]['enabled'] ) ? 0 : 1;
			$values[] = "('{$type}', '{$subject}', '{$body}', b'{$enabled}')";
		}

		if ( ! empty( $values ) ) {
            $values = implode( ',', $values );
            $wpdb->query(
                "INSERT INTO `{$notices_table}`
                (`type`, `subject`, `body`, `enabled`) VALUES
                {$values}
                ON DUPLICATE KEY
                UPDATE
                    `subject`=VALUES(`subject`),
                    `body`=VALUES(`body`),
                    `enabled`=VALUES(`enabled`);"

            );
        }
		if ( NULL !== $options ) {
			return $this->options;
		}
	}

	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function tab_user_emails() {
		global $bws_bkng;
        $this->tab_title( __( 'Customer Notifications', BWS_BKNG_TEXT_DOMAIN ) ); ?>
		<table class="form-table">
			<?php
			/**
			 * "Send from" option
			 */
			$name  = 'bkng_from_value';
			$value = 'agent';
			$attr  = isset( $this->options['from_value'] ) && $value == $this->options['from_value'] ? 'checked="checked"' : '';
			$agents_radiobox = $bws_bkng->get_radiobox( compact( 'name', 'value', 'attr' ) );

			$value = 'custom';
			$attr  = isset( $this->options['from_value'] ) && $value == $this->options['from_value'] ? 'checked="checked"' : '';
			$custom_radiobox = $bws_bkng->get_radiobox( compact( 'name', 'value', 'attr' ) );

			if ( empty( $this->agents_data ) ) {
				$agents_list = $bws_bkng->get_errors( __( 'No users allowed to send notifications to clients. Please check the users\' capabilities on your site.', BWS_BKNG_TEXT_DOMAIN ) );
			} else {
				$name = 'bkng_agent_sender_email';
				$options = array();
				$after =  $bws_bkng->get_info( __( 'Select the user on whose behalf customers will receive notifications', BWS_BKNG_TEXT_DOMAIN ) . '.' );
				foreach( $this->agents_data as $data ) {
					$options[ $data['user_email'] ] = $data['user_nicename'];
				}
				$selected = isset( $this->options['agent_sender_email'] ) ? $this->options['agent_sender_email'] : '';
				$agents_list = $bws_bkng->get_select( compact( 'name', 'selected', 'options', 'after' ) );
			}

			$name   = 'bkng_from_name';
			$value  = isset( $this->options['from_name'] ) ? $this->options['from_name'] : '';
			$before = __( 'name', BWS_BKNG_TEXT_DOMAIN );
			$name_input = $bws_bkng->get_text_input( compact( 'name', 'value', 'before' ) );

			$name   = 'bkng_from_email';
			$value  = isset( $this->options['from_email'] ) ? $this->options['from_email'] : '';
			$before = __( 'email', BWS_BKNG_TEXT_DOMAIN );
			$email_input = $bws_bkng->get_text_input( compact( 'name', 'value', 'before' ) );
			$info        = $bws_bkng->get_info( __( 'Enter custom data for "From" field', BWS_BKNG_TEXT_DOMAIN ) . '.' );
			$content     = "<p>{$agents_radiobox}{$agents_list}</p><p>{$custom_radiobox}{$name_input}{$email_input}{$info}</p>";

			$bws_bkng->display_table_row( __( 'Send from', BWS_BKNG_TEXT_DOMAIN ), $content );

			$this->display_list_editors( $this->orders['to_customer'] ); ?>
		</table>

	<?php }

	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function display_tab_user_emails() {
		global $bws_bkng;
        $this->tab_title( __( 'Customer Notifications', BWS_BKNG_TEXT_DOMAIN ) ); ?>
		<table class="form-table">
			<?php
			/**
			 * "Send from" option
			 */
			$name  = 'bkng_from_value';
			$value = 'agent';
			$attr  = isset( $this->options['from_value'] ) && $value == $this->options['from_value'] ? 'checked="checked"' : '';
			$agents_radiobox = $bws_bkng->get_radiobox( compact( 'name', 'value', 'attr' ) );

			$value = 'custom';
			$attr  = isset( $this->options['from_value'] ) && $value == $this->options['from_value'] ? 'checked="checked"' : '';
			$custom_radiobox = $bws_bkng->get_radiobox( compact( 'name', 'value', 'attr' ) );

			if ( empty( $this->agents_data ) ) {
				$agents_list = $bws_bkng->get_errors( __( 'No users allowed to send notifications to clients. Please check the users\' capabilities on your site.', BWS_BKNG_TEXT_DOMAIN ) );
			} else {
				$name = 'bkng_agent_sender_email';
				$options = array();
				$after =  $bws_bkng->get_info( __( 'Select the user on whose behalf customers will receive notifications', BWS_BKNG_TEXT_DOMAIN ) . '.' );
				foreach( $this->agents_data as $data ) {
					$options[ $data['user_email'] ] = $data['user_nicename'];
				}
				$selected = isset( $this->options['agent_sender_email'] ) ? $this->options['agent_sender_email'] : '';
				$agents_list = $bws_bkng->get_select( compact( 'name', 'selected', 'options', 'after' ) );
			}

			$name   = 'bkng_from_name';
			$value  = isset( $this->options['from_name'] ) ? $this->options['from_name'] : '';
			$before = __( 'name', BWS_BKNG_TEXT_DOMAIN );
			$name_input = $bws_bkng->get_text_input( compact( 'name', 'value', 'before' ) );

			$name   = 'bkng_from_email';
			$value  = isset( $this->options['from_email'] ) ? $this->options['from_email'] : '';
			$before = __( 'email', BWS_BKNG_TEXT_DOMAIN );
			$email_input = $bws_bkng->get_text_input( compact( 'name', 'value', 'before' ) );
			$info        = $bws_bkng->get_info( __( 'Enter custom data for "From" field', BWS_BKNG_TEXT_DOMAIN ) . '.' );
			$content     = "<p>{$agents_radiobox}{$agents_list}</p><p>{$custom_radiobox}{$name_input} {$email_input}<br />{$info}</p>";

			$bws_bkng->display_table_row( __( 'Send from', BWS_BKNG_TEXT_DOMAIN ), $content );
			$this->display_list_editors( $this->orders['to_customer'] ); ?>
		</table>

	<?php }

	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function tab_admin_emails() {
		global $bws_bkng;
		$this->tab_title( __( 'Agent Notifications', BWS_BKNG_TEXT_DOMAIN ) ); ?>
		<table class="form-table">
			<?php
			/**
			 * "Send to" option
			 */
			$name      = 'bkng_additional_recipient_emails';
			$value     =  ! empty( $this->options['additional_recipient_emails'] ) ? implode( ', ', $this->options['additional_recipient_emails'] ) : '';
			$maxlength = 1000;
			$after     = $bws_bkng->get_info( __( 'Enter additional comma-separated emails of users who will receive notifications', BWS_BKNG_TEXT_DOMAIN ) . '.' );
			$additional_input = $bws_bkng->get_text_input( compact( 'name', 'value', 'maxlength', 'after' ) );

			if ( empty( $this->agents_data ) ) {
				$agents_list = '';
			} else {
				$name   = 'bkng_agent_recipient_emails[]';
				$option = isset( $this->options['agent_recipient_emails'] ) ? $this->options['agent_recipient_emails'] : '';
				$agents_list = array();
				foreach( $this->agents_data as $data ) {
					$value = $data['user_email'];
					$attr  = ! empty( $option ) && in_array( $data['user_email'], (array)$option ) ? 'checked="checked"' : '';
					$id    = 'agent_recipient_emails' . sanitize_title( $data['user_nicename'] ) . mt_rand( 0, 1000 );
					$after = $data['user_nicename'];
					$class = '';
					$agents_list[] = '<li>' . $bws_bkng->get_checkbox( compact( 'name', 'value', 'attr', 'id', 'class', 'after' ) ) . '</li>';
				}
				$info = $bws_bkng->get_info( __( 'Choose the agent(s) that will receive service notifications', BWS_BKNG_TEXT_DOMAIN ) . '.' );
				$agents_list = $info . '<ul class="bkng_meta_list" id="bkng_agents_list">' . implode( '', $agents_list ) . '</ul>';
			}
			$content = empty( $agents_list ) ? $additional_input : "{$agents_list}<p>{$additional_input}</p>";

			$bws_bkng->display_table_row( __( 'Send to', BWS_BKNG_TEXT_DOMAIN ), $content );

			$this->display_list_editors( $this->orders['to_agent'] ); ?>
		</table>
	<?php }

	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function display_tab_admin_emails() {
		global $bws_bkng;
		$this->tab_title( __( 'Agent Notifications', BWS_BKNG_TEXT_DOMAIN ) ); ?>
		<table class="form-table">
			<?php
			/**
			 * "Send to" option
			 */
			$name      = 'bkng_additional_recipient_emails';
			$value     =  ! empty( $this->options['additional_recipient_emails'] ) ? implode( ', ', $this->options['additional_recipient_emails'] ) : '';
			$maxlength = 1000;
			$after     = $bws_bkng->get_info( __( 'Enter additional comma-separated emails of users who will receive notifications', BWS_BKNG_TEXT_DOMAIN ) . '.' );
			$additional_input = $bws_bkng->get_text_input( compact( 'name', 'value', 'maxlength', 'after' ) );

			if ( empty( $this->agents_data ) ) {
				$agents_list = '';
			} else {
				$name   = 'bkng_agent_recipient_emails[]';
				$option = isset( $this->options['agent_recipient_emails'] ) ? $this->options['agent_recipient_emails'] : '';
				$agents_list = array();
				foreach( $this->agents_data as $data ) {
					$value = $data['user_email'];
					$attr  = ! empty( $option ) && in_array( $data['user_email'], (array)$option ) ? 'checked="checked"' : '';
					$id    = 'agent_recipient_emails' . sanitize_title( $data['user_nicename'] ) . mt_rand( 0, 1000 );
					$after = $data['user_nicename'];
					$class = '';
					$agents_list[] = '<li>' . $bws_bkng->get_checkbox( compact( 'name', 'value', 'attr', 'id', 'class', 'after' ) ) . '</li>';
				}
				$info = $bws_bkng->get_info( __( 'Choose the agent(s) that will receive service notifications', BWS_BKNG_TEXT_DOMAIN ) . '.' );
				$agents_list = $info . '<ul class="bkng_meta_list" id="bkng_agents_list">' . implode( '', $agents_list ) . '</ul>';
			}
			$content = empty( $agents_list ) ? $additional_input : "{$agents_list}<p>{$additional_input}</p>";

			$bws_bkng->display_table_row( __( 'Send to', BWS_BKNG_TEXT_DOMAIN ), $content );

			$this->display_list_editors( $this->orders['to_agent'] ); ?>
		</table>
	<?php }

	/**
	 * Displays the fields to edit notification data
	 * @since  0.1
	 * @access private
	 * @param  array      $emails_list    The list of notifications data
	 * @return void
	 */
	private function display_list_editors( $emails_list ) {
		global $bws_bkng, $wpdb;
		$table       = BWS_BKNG_DB_PREFIX . $this->post_type . '_notifications';
		$types       = "'" . implode( "', '", array_keys( $emails_list ) ) . "'";

		/**
		 * ORDER BY FIELD(`type`,{$types}) clause is used to display options
		 * according to the order of assignment of statuses to users' orders
		 */
		$emails_data = $wpdb->get_results(
            "SELECT * FROM `{$table}` WHERE `type` IN ($types) ORDER BY FIELD(`type`,$types);"
        );

		if ( empty( $emails_data ) ) {
			echo $bws_bkng->get_errors( __( 'Cannot get emails info', BWS_BKNG_TEXT_DOMAIN ) );
			return;
		}

		foreach( $emails_data as $data ) {

			$name     = "bkng_notice[{$data->type}][enabled]";
			$attr     = empty( $data->enabled ) ? '' : 'checked="checked"';
			$attr    .= "data-affect-show=\".bkng_email_settings_{$data->type}\"";
			$after    = $bws_bkng->get_info( $emails_list[ $data->type ][1] . '.' );
			$class    = 'bws_option_affect';
			$checkbox = $bws_bkng->get_checkbox( compact( 'name', 'attr', 'after', 'class' ) );

			$name  = "bkng_notice[{$data->type}][subject]";
			$value = $data->subject;
			$class = $id = 'bkng_email_subject';

			if( empty( $data->enabled ) )
				$attr  = 'placeholder="' . __( 'Enter subject', BWS_BKNG_TEXT_DOMAIN ) . '" required=""';
			else
				$attr  = 'placeholder="' . __( 'Enter subject', BWS_BKNG_TEXT_DOMAIN ) . '" required="required"';

			$subject = $bws_bkng->get_text_input( compact( 'name', 'value', 'class', 'attr', 'id' ) );
			$body    = $bws_bkng->get_notice_editor( $data->type, false, $data->body );

			$bws_bkng->display_table_row( $emails_list[ $data->type ][0], $checkbox );

			$row_class = "bkng_email_settings_{$data->type}";
			$bws_bkng->display_table_row( '', $subject, $row_class );
			$bws_bkng->display_table_row( '', $body, $row_class );
		}
	}
}
