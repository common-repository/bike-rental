<?php
/**
 * Displays the content of the single order edit page
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Single_Order_Page' ) )
	return;

class BWS_BKNG_Single_Order_Page {
	private static $instance = NULL;

	/**
	 * An instance of BWS_BKNG_Order class are used to upodate the order data
	 * @since  0.1
	 * @access private
	 * @var object
	 */
	private $order_handler;

	/**
	 * The currently viewed/edited order ID
	 * @since  0.1
	 * @access private
	 * @var int
	 */
	private $order_id;

	/**
	 * An instance of BWS_BKNG_Mailer class are used to handle notification sending
	 * @since  0.1
	 * @access private
	 * @var object
	 */
	private $mailer;

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
	 * Constructor of class
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function __construct() {

		$this->order_id      = absint( $_GET['bkng_order_id'] );
		$this->order_handler = BWS_BKNG_Order::get_instance();
		$this->mailer        = new BWS_BKNG_Mailer();

		$result = $this->process_action();

		if ( ! empty( $result ) )
			$this->clear_query( $result );
	}

	/**
	 * Displays the page content
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function display_table() {
		global $bws_bkng;

		$order_data = $this->order_handler->get();
		$errors     = $this->order_handler->get_errors();
		$errors     = is_wp_error( $errors ) ? $errors : $this->mailer->get_errors();
		?>

		<div class="wrap bkng_single_order_page">

			<h1 class="wp-heading-inline"><?php _e( 'Order', BWS_BKNG_TEXT_DOMAIN ); ?></h1>

			<?php if ( $errors )
				echo $bws_bkng->get_errors( $errors );

			$this->show_notices(); ?>
			<div id="bws_save_settings_notice" class="updated fade below-h2" style="display: none;">
				<p>
					<strong><?php _e( 'Notice', BWS_BKNG_TEXT_DOMAIN); ?></strong>:&nbsp;<?php _e( "The order's data have been changed.", BWS_BKNG_TEXT_DOMAIN ); ?>&nbsp;<a class="bws_save_anchor" href="#bws-submit-button"><?php _e( 'Save Changes', BWS_BKNG_TEXT_DOMAIN ); ?></a>
				</p>
			</div>
			<form id="poststuff" class="bws_form" method="post">
				<div id="bkng_order_details">
					<div id="bkng_order_meta">

						<div id="bkng_order_general_details" class="postbox">
							<h2 class="hndle"><?php _e( 'Order Details', BWS_BKNG_TEXT_DOMAIN ); ?></h2>
							<div class="inside">
								<table class="form-table">
									<?php
									do_action( 'bws_bkng_single_order_details_before', $order_data );
									$bws_bkng->display_table_row( __( 'ID', BWS_BKNG_TEXT_DOMAIN ), absint( $order_data['id'] ) );

									$content = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $order_data['date_create'] ) );
									$bws_bkng->display_table_row( __( 'Date', BWS_BKNG_TEXT_DOMAIN ), $content );

									$name     = 'bkng_order_status';
									$options  = $bws_bkng->get_order_statuses();
									$selected = $order_data['status'];
									$content  = $bws_bkng->get_select( compact( 'name', 'options', 'selected' ) );
									$bws_bkng->display_table_row( __( 'Status', BWS_BKNG_TEXT_DOMAIN ), $content );
									do_action( 'bws_bkng_single_order_details_after', $order_data );
									?>
								</table>
							</div><!-- .inside -->
						</div><!-- #bkng_order_general_details.postbox -->

						<div id="bkng_order_billing_details" class="postbox">
							<h2 class="hndle"><?php _e( 'Customer Details', BWS_BKNG_TEXT_DOMAIN ); ?></h2>
							<div class="inside">
								<table class="form-table">
									<?php do_action( 'bws_bkng_single_order_customers_details_before', $order_data );

										$content = "{$order_data['user_firstname']}&nbsp;{$order_data['user_lastname']}";
										if ( ! empty( $order_data['user_id'] ) ) {
											$user = get_user_by( 'id', $order_data['user_id'] );
											if ( $user instanceof WP_User ) {
												$user_link = get_edit_user_link( $user->ID );
												$content .= "(<a href=\"{$user_link}\">{$user->data->user_login}</a>)";
											}
										}
										$bws_bkng->display_table_row( __( 'Name', BWS_BKNG_TEXT_DOMAIN ), $content );
										$content = "<a href=\"mailto:{$order_data['user_email']}\">{$order_data['user_email']}</a>";
										$bws_bkng->display_table_row( __( 'Email', BWS_BKNG_TEXT_DOMAIN ), $content );
										
										do_action( 'bws_bkng_single_order_customers_details_after', $order_data ); ?>
								</table>
							</div><!-- .inside -->
						</div><!-- #bkng_order_general_details.postbox -->

					</div><!-- #bkng_order_meta -->

					<div id="bkng_additional_data" class="postbox">
						<h2 class="hndle"><?php _e( 'Additional Info', BWS_BKNG_TEXT_DOMAIN ); ?></h2>
						<div class="inside">
							<table class="form-table">
								<?php
								$rent_interval = $order_data['products'][ key( $order_data['products'] ) ]['rent_interval'];

								$content       = "on_hold" == $order_data['status'] ? bws_bkng_datetimepicker_form( "bkng_product_datepicker[%s]", $rent_interval['from'], $rent_interval['till'], true ) : bws_bkng_datetimepicker_data( $rent_interval['from'], $rent_interval['till'], true );
								$bws_bkng->display_table_row( __( 'Pick-up drop-off dates', BWS_BKNG_TEXT_DOMAIN ), $content );

								do_action( 'bws_bkng_single_order_bkng_additional_data', $order_data );
								?>
							</table>
						</div><!-- .inside -->
					</div><!-- #bkng_additional_info.postbox -->

					<div id="bkng_order_products_list" class="postbox">
						<?php $products_list = new BWS_BKNG_Single_Order_Products_List( $order_data );
						$products_list->display(); ?>
					</div><!-- #bkng_order_products_list.postbox -->

				</div><!-- #bkng_order_details -->

				<div id="bkng_order_widgets">

					<div id="bkng_user_message" class="postbox">
						<h2 class="hndle"><?php _e( 'Customer Note', BWS_BKNG_TEXT_DOMAIN ); ?></h2>
						<div class="inside">
							<p><?php echo empty( $order_data['user_message'] ) ? '<i>' . __( 'There are no notes from the customer', BWS_BKNG_TEXT_DOMAIN ) . '</i>' : esc_html( $order_data['user_message'] ) ?></p>
						</div><!-- .inside -->
					</div><!-- #bkng_user_message.postbox -->

					<div id="bkng_order_actions" class="postbox">
						<h2 class="hndle"><?php _e( 'Order Actions', BWS_BKNG_TEXT_DOMAIN ); ?></h2>
						<div class="inside">
							<?php $name = "bkng_order_actions";
							$options = array(
								'save'    => __( 'Save Changes', BWS_BKNG_TEXT_DOMAIN ),
								're-send' => __( 'Re-send', BWS_BKNG_TEXT_DOMAIN )
							);
							$selected = 'save';
							echo $bws_bkng->get_select( compact( 'name', 'options', 'selected' ) ); ?>
						</div>

						<div id="major-publishing-actions">
							<div id="delete-action">
								<?php
								$post  = BWS_BKNG_POST;
								$class = 'submitdelete deletion';
								$attr  = 'style="color: red;"';
								$href  = wp_nonce_url( "edit.php?post_type={$post}&amp;page=bkng_orders&amp;bkng_order_id={$this->order_id}", 'bkng_order_list_nonce' ) . "&amp;action=delete";
								$text  = __( 'Delete', BWS_BKNG_TEXT_DOMAIN );
								echo $bws_bkng->get_link( compact( 'class', 'href', 'text', 'attr' ) ); ?>
							</div>
							<div id="publishing-action">
								<?php $id = "bws-submit-button";
								$class = "button button-primary button-large";
								$name  = "bkng_save_order";
								$value = __( 'Submit', BWS_BKNG_TEXT_DOMAIN );
								echo $bws_bkng->get_button_input( compact( 'id', 'class', 'name', 'value' ) );
								wp_nonce_field( plugin_basename( __FILE__ ), 'bkng_order_nonce' ); ?>
							</div>
							<div class="clear"></div>
						</div>

					</div><!-- #bkng_order_actions.postbox -->

				</div><!-- #bkng_order_widgets -->

			</form><!-- .poststuff -->

		</div><!-- .wrap.bkng_single_order_page -->

	<?php }


	/**
	 * Handle the actions form the edit page.
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return false|array      The array of link arguments for redirect in case of success, false otherwise
	 */
	private function process_action() {

		if ( ! isset( $_POST['bkng_save_order'] ) )
			return false;

		check_admin_referer( plugin_basename( __FILE__ ), 'bkng_order_nonce' );

		$actions = array( "re-send", "delete" );

		$is_action_query = empty( $_POST['bkng_order_actions'] ) || ! in_array( $_POST['bkng_order_actions'], $actions ) ? false : sanitize_text_field( stripslashes( $_POST['bkng_order_actions'] ) );

		if ( $is_action_query ) {
			switch ( $is_action_query ) {
				case 're-send':
					$new_status = empty( $_POST['bkng_order_status'] ) ? false : sanitize_text_field( stripslashes( $_POST['bkng_order_status'] ) );

					$result = $this->order_handler->update_order( $new_status );

					if ( $this->order_handler->get_errors() )
						return false;

					$args = array(
						'id'         => $this->order_id,
						'status'     => $new_status,
						'force_send' => 'customer',
						'add_order'  => 'customer'
					);

					if ( 'status_changed' == $result )
						$args['send'] = 'agent';

					$this->mailer->send_order_notes( $args );

					return $this->mailer->get_errors() ? false : array( 'result' => 'sent', 'count' => 1 );
				default:
					break;
			}

		} else {

			$new_status = empty( $_POST['bkng_order_status'] ) ? false : sanitize_text_field( stripslashes( $_POST['bkng_order_status'] ) );

			$result = $this->order_handler->update_order( $new_status );

			if ( ! $this->order_handler->get_errors() ) {
				switch ( $result ) {
					case 'status_changed':
						$order_data = $this->order_handler->get();

						$args = array(
							'id'     => $this->order_id,
							'status' => $order_data['status'],
							'send'   => 'both',
						);
						$this->mailer->send_order_notes( $args );

						if ( $this->mailer->get_errors() )
							break;

					case 'saved':
						$link_param = $result;
						break;
					default:
						break;
				}

				return empty( $link_param ) ? false : array( 'result' => $link_param, 'count' => 1 );
			}
		}

		return false;
	}

	/**
	 * Remove exceed query parameters and makes a redirect to the current page in order to avoid
	 * re-handling the request during page re-loading.
	 * @since  0.1
	 * @access private
	 * @param  array     $args     An array of data that need to be added to the url
	 * @return void
	 */
	private function clear_query( $args ) {
		$sendback = remove_query_arg( array( '_wpnonce' ), wp_get_referer() );
		$sendback = add_query_arg( $args, $sendback );
		wp_redirect( add_query_arg( $args, $sendback ) );
		exit();
	}

	/**
	 * Displays the necessary message after the handling of the request
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return void
	 */
	private function show_notices() {

		if ( ! isset( $_GET['result'] ) )
			return;

		switch( $_GET['result'] ) {
			case 'sent':
				$message = __( "Order re-sent successfully", BWS_BKNG_TEXT_DOMAIN );
				break;
			case 'saved':
				$message = __( "Order saved successfully", BWS_BKNG_TEXT_DOMAIN );
				break;
			case 'status_changed':
				$message = __( "Order status saved successfully", BWS_BKNG_TEXT_DOMAIN );
				break;
			default:
				break;
		}

		if ( ! empty( $message ) ) { ?>
			<div class="updated fade inline notice notice-success is-dismissible">
				<p><?php echo esc_html( $message ); ?></p>
				<button type="button" class="notice-dismiss"></button>
			</div>
		<?php }
	}
}
