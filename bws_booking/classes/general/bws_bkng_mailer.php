<?php
/**
 * Handle the mail sending
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Mailer' ) )
	return;

class BWS_BKNG_Mailer {

	/**
	 * The currently handled notification type data
	 * @since    0.1
	 * @access private
	 * @var array
	 */
	private $mail_data;

	/**
	 * The order handler
	 * @since    0.1
	 * @access private
	 * @var object
	 */
	private $order_handler;

	/**
	 * The database table name that keps notifications data
	 * @since    0.1
	 * @access private
	 * @var string
	 */
	private $table;

	/**
	 * The list of errors occurred during mail sending
	 * @since    0.1
	 * @access private
	 * @var object
	 */
	private $errors;

	private $post_type;

	/**
	 * Fetch the list of available shortcode
	 * @since    0.1
	 * @static
	 * @access public
	 * @param  void
	 * @return array
	 */
	public static function get_shortcodes() {
		$shortcodes = array(
			'site_title' => array(
				'description' => __( 'Adds the site title', BWS_BKNG_TEXT_DOMAIN ),
				'callback'    => ''
			),
			'order_number' => array(
				'description' => __( 'Adds the order ID', BWS_BKNG_TEXT_DOMAIN ),
				'callback'    => ''
			),
			'order_date' => array(
				'description' => __( 'Adds the order creation date', BWS_BKNG_TEXT_DOMAIN ),
				'callback'    => ''
			),
			'rent_interval' => array(
				'description' => __( 'Adds the ordered products rent interval', BWS_BKNG_TEXT_DOMAIN ),
				'callback'    => ''
			),
			'ordered_products' => array(
				'description' => __( 'Adds the list of ordered products data with the price summaries', BWS_BKNG_TEXT_DOMAIN ),
				'callback'    => ''
			),
			'billing_details' => array(
				'description' => __( 'Adds customer data entered during placing the order', BWS_BKNG_TEXT_DOMAIN ),
				'callback'    => ''
			),
			'customer_name' => array(
				'description' => __( "Adds customer's full name entered during placing the order", BWS_BKNG_TEXT_DOMAIN ),
				'callback'    => ''
			),
			'order_status' => array(
				'description' => __( 'Adds the order status', BWS_BKNG_TEXT_DOMAIN ),
				'callback'    => ''
			)
		);
		/**
		 * Hook allows to register new shortcodes and function of its processing
		 */
		return array_merge( $shortcodes, (array)apply_filters( 'bws_bkng_mail_shortcodes', array() ) );
	}

	/**
	 * Class constructor
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function __construct() {
		/**
		 * Catch wp_mail() errors
		 * @see wp-includes/pluggable.php wp_mail() definition
		 */
		add_action( 'wp_mail_failed', array( $this, 'get_wp_mail_error' ) );

		$this->order_handler = BWS_BKNG_Order::get_instance();
		$this->post_type = $this->order_handler->get_order_post_type();
	}

	/**
	 * Class constructor
	 * @since  0.1
	 * @access public
	 * @param  object  $error  The instance of WP_Error object
	 * @return void
	 */
	public function get_wp_mail_error( $error ) {

		if ( ! is_wp_error( $error ) )
			return;

		if ( is_wp_error( $this->errors ) )
			$this->errors->add( $error->get_error_code(), $error->get_error_message() );
		else
			$this->errors = $error;
	}

	/**
	 * Handles e-mail sending
	 * @since  0.1
	 * @access public
	 * @param  array  $args    The list of parameters
	 * @return void
	 */
	public function send_order_notes( $args = '' ) {
		global $bws_bkng, $wpdb;

		$this->table = BWS_BKNG_DB_PREFIX . $this->post_type . '_notifications';

		$defaults = array(
			/**
			 * The order ID
			 * @var int
			 */
			'id' => '',
			/**
			 * The order status
			 * @var string
			 */
			'status' => 'on_hold',
			'send'   => false,
			/**
			 * Whether to send order notification not depending on plugin settings
			 *
			 * @var boolean|string    False - send emails according to the plugin settings,
			 *                        string ( 'customer', 'agent', 'both' ) - send the notification
			 *                        not depending on the plugin settings
			 */
			'force_send' => false,
			'add_order'  => false
		);

		$this->mail_data = wp_parse_args( (array)$args, $defaults );

		if (
			empty( $this->mail_data['id'] ) ||
			empty( $this->mail_data['status'] ) ||
			! in_array( $this->mail_data['status'], array_keys( $bws_bkng->get_order_statuses() ) )
		) {
			$this->add_error( 'no_order_data' );
			return;
		}

		foreach( array( 'add_order', 'send', 'force_send' ) as $option ) {
			switch( $this->mail_data[ $option ] ) {
				case 'customer':
				case 'agent':
					$this->mail_data[ $option ] = (array)$this->mail_data[ $option ];
					break;
				case 'both':
					$this->mail_data[ $option ] = array( 'customer', 'agent' );
					break;
				default:
					$this->mail_data[ $option ] = false;
					break;
			}
		}

		foreach( array( 'customer', 'agent' ) as $group ) {

			$this->mail_data['group'] = $group;
			$this->mail_data['type']  = "{$group}_{$this->mail_data['status']}_order";

			if ( ! $this->sending_allowed() )
				continue;

			$this->prepare_email();

			if ( $this->get_errors() )
				return;

			extract( $this->mail_data );

			if ( ! wp_mail( $user_email, $subject, $body, $headers ) )
				return;
		}
	}

	/**
	 * Replaces shortcodes with the appropriate content
	 * @since  0.1
	 * @access public
	 * @param  string  $shortcode    The shortcode
	 * @return string
	 */
	public function replace_shortcode( $shortcode ) {
		global $bws_bkng;
		$order_data     = $this->order_handler->get();
		$billing_data   = $this->order_handler->get_billing_data();
		$order_statuses = $bws_bkng->get_order_statuses();
		$is_new_order   = $this->order_handler->is_place_order_query();
		$shortcodes     = self::get_shortcodes();

		if ( in_array( $shortcode[1], array_keys( $shortcodes ) ) && ! empty( $shortcodes[ $shortcode[1] ]['callback'] ) )
			return call_user_func( $shortcodes[ $shortcode[1] ]['callback'], $this->mail_data, $order_data );


		switch( $shortcode[1] ) {
			case 'site_title':
				$content = get_bloginfo( 'name' );
				break;
			case 'order_number':
				$content = "#{$this->mail_data['id']}";
				break;
			case 'order_date':
				$content = date_i18n( get_option( 'date_format' ), strtotime( $order_data['date_create'] ) );
				break;
			case 'rent_interval':
				$rent_interval = $order_data['products'][ key( $order_data['products'] ) ]['rent_interval'];
				$date_dormat   = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
				$content = date_i18n( $date_dormat, $rent_interval['from'] ) . '&nbsp;-&nbsp;' . date_i18n( $date_dormat, $rent_interval['till'] );
				break;
			case 'ordered_products':
				$content = $this->order_handler->table( true, true );
				break;
			case 'billing_details':
				$first_name_label = __( 'First Name', BWS_BKNG_TEXT_DOMAIN );
				$last_name_label  = __( 'Last Name', BWS_BKNG_TEXT_DOMAIN );
				$phone_label      = __( 'Phone', BWS_BKNG_TEXT_DOMAIN );
				$email_label      = __( 'Email', BWS_BKNG_TEXT_DOMAIN );
				$message_label    = __( 'Message', BWS_BKNG_TEXT_DOMAIN );
				$content = "<table>
					<tr>
						<td style=\"min-width: 120px;\"><strong>{$first_name_label}</strong></td>
						<td>{$billing_data['user_firstname']}</td>
					</tr>
					<tr>
						<td style=\"min-width: 120px;\"><strong>{$last_name_label}</strong></td>
						<td>{$billing_data['user_lastname']}</td>
					</tr>
					<tr>
						<td style=\"min-width: 120px;\"><strong>{$phone_label}</strong></td>
						<td>{$billing_data['user_phone']}</td>
					</tr>
					<tr>
						<td style=\"min-width: 120px;\"><strong>{$email_label}</strong></td>
						<td>{$billing_data['user_email']}</td>
					</tr>
					<tr>
						<td style=\"min-width: 120px;\"><strong>{$message_label}</strong></td>
						<td>{$billing_data['user_message']}</td>
					</tr>
				</table>";
				break;
			case 'customer_name':
				$content = $is_new_order ? "{$billing_data['user_firstname']}&nbsp;{$billing_data['user_lastname']}" : "{$order_data['
				break;user_firstname']}&nbsp;{$order_data['user_lastname']}";
				break;
			case 'order_status':
				$content = $order_statuses[ $order_data['status'] ];
				break;
			default:
				$content = $shortcode[1];
				break;
		}
		return apply_filters( 'bws_bkng_replace_mail_shortcode', $content, $shortcode[1], $this->mail_data, $order_data );
	}

	/**
	 * Fetch the error message accrording to the given error codes
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   object|null         An instance of the class WP_Error in case if some errors occurred, null otherwise
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Adds error to the list.
	 * @since    0.1
	 * @access   private
	 * @param    string $code
	 * @return   void
	 */
	private function add_error( $code ) {
		if ( ! is_wp_error( $this->errors ) )
			$this->errors = new WP_Error();

		switch( $code ) {
			case 'no_order_data':
				$message = __( "Can't get order data", BWS_BKNG_TEXT_DOMAIN );
				break;
			case 'no_recipient':
				$message = __( "Can't get email recipient data", BWS_BKNG_TEXT_DOMAIN );
				break;
			case 'no_mail_data':
				$message = __( "Can't get email data", BWS_BKNG_TEXT_DOMAIN );
				break;
			default:
				$message = apply_filters( 'bws_bkng_mail_errors', '', $code );
				break;
		}
		if ( ! empty( $message ) )
			$this->errors->add( $code, $message );
	}

	/**
	 * Prepare the e-mail data before sending.
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   void
	 */
	private function prepare_email() {
		global $wpdb, $bws_bkng;

		$raw_mail_data = $this->get_mail_raw_data();

		if ( ! $raw_mail_data )
			return false;

		if ( in_array( $this->mail_data['group'], (array)$this->mail_data['add_order'] ) )
			$raw_mail_data['body'] = $this->get_order_pattern();

		$this->mail_data['user_email'] = $this->get_user_email();
		$this->mail_data['subject']    = $this->handle_shortcodes( $raw_mail_data['subject'] );
		$this->mail_data['body']       = wpautop( $this->handle_shortcodes( $raw_mail_data['body'] ) );
		$this->mail_data['headers']    = $this->get_headers();

		return true;
	}

	private function sending_allowed() {
		global $wpdb;

		$group = $this->mail_data['group'];

		return
			in_array( $group, (array)$this->mail_data['force_send'] ) ||
			(
				in_array( $group, (array)$this->mail_data['send'] ) &&
				$wpdb->get_var(
				    $wpdb->prepare(
                        "SELECT `enabled` FROM `{$this->table}` WHERE `type`=%s LIMIT 1;",
                        $this->mail_data['type']
                    )
                )
			);
	}

	/**
	 * Fetch the e-mail data from database.
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   boolean|array    The array of mail data, false otherwise
	 */
	private function get_mail_raw_data() {
		global $wpdb;

		$mail_data = $wpdb->get_row(
		    $wpdb->prepare(
                "SELECT * FROM `{$this->table}` WHERE `type`=%s LIMIT 1;",
                $this->mail_data['type']
            ),
            ARRAY_A
        );

		if ( $wpdb->last_error || empty( $mail_data ) ) {
			$this->add_error( 'no_mail_data' );
			return false;
		}

		return $mail_data;
	}

	/**
	 * Fetch the e-mail of recipient.
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   boolean|array    The array of mail data, false otherwise
	 */
	private function get_user_email() {
		global $bws_bkng;
		$order_data   = $this->order_handler->get();
		$billing_data = $this->order_handler->get_billing_data();

		switch( $this->mail_data['group'] ) {
			case 'customer':
				return $this->order_handler->is_place_order_query() ? $billing_data['user_email'] : $order_data['user_email'];
			case 'agent':
				return implode( ',', array_unique( array_merge(
					(array)$bws_bkng->get_option( "agent_recipient_emails" ),
					(array)$bws_bkng->get_option( "additional_recipient_emails" )
				) ) );
				break;
			default:
				$this->add_error( 'no_mail_data' );
				return false;
		}
	}

	/**
	 * Fetch the e-mail headers.
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   boolean|string    The e-mail headers, false otherwise
	 */
	private function get_headers() {
		global $bws_bkng;

		switch( $this->mail_data['group'] ) {
			case 'customer':
				$from_name = $from_email = '';

				if ( 'agent' == $bws_bkng->get_option( "from_value" ) ) {
					$from_email = $bws_bkng->get_option( "agent_sender_email" );
					$user       = get_user_by( 'email', $from_email );
					$from_name  = $user->user_nicename;
				} else {
					$from_email = $bws_bkng->get_option( "from_email" );
					$from_name  = $bws_bkng->get_option( "from_name" );
				}
				break;
			case 'agent':
				$from_name  = get_bloginfo( 'name' );
				$from_email = get_bloginfo( 'admin_email' );
				break;
			default:
				$this->add_error( 'no_mail_data' );
				return false;
		}
		return "Content-type: text/html; charset=utf-8\nFrom:{$from_name} <{$from_email}>";
	}

	/**
	 * Fetch shortcodes in the e-mail subject and body.
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   boolean|string    The e-mail headers, false otherwise
	 */
	private function handle_shortcodes( $content ) {
		return stripslashes( preg_replace_callback( "/\{([a-zA-Z-_]+)\}/", array( $this, 'replace_shortcode' ), $content ) );
	}

	/**
	 * Fetch the notification to add order data to the list not depending on the notifictaion message content.
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   string
	 */
	private function get_order_pattern() {
		return apply_filters(
			'bws_bkng_order_pattern',
			'<p>'. __( 'Order number', BWS_BKNG_TEXT_DOMAIN ) . ': <strong>{order_number}</strong> '. __( 'from', BWS_BKNG_TEXT_DOMAIN ) . ' <strong>{order_date}</strong>.</p>
			<p>'. __( 'Order status', BWS_BKNG_TEXT_DOMAIN ) . ': <strong>{order_status}</strong>.</p>
			<p>'. __( 'Pick-up drop-off dates', BWS_BKNG_TEXT_DOMAIN ) . ': <strong>{rent_interval}</strong>.</p>
			<p>'. __( 'Ordered products', BWS_BKNG_TEXT_DOMAIN ) . ':</p>
			{ordered_products}
			<p>'. __( 'Billing details', BWS_BKNG_TEXT_DOMAIN ) . ':</p>
			{billing_details}'
		);
	}
}