<?php /**
 * Contains the functions for adaption the Booking Core for plugin
 * @since Bike Rental 1.0.0
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( ! class_exists( 'BKRNTL_Locations' ) ) {

	class BKRNTL_Locations {

		/**
		 * The class constructor.
		 * @since  1.0.0
		 * @access public
		 * @param  void
		 * @return void
		 */
		public function __construct() {
			add_action( 'wp', array( $this, 'save_cookies' ), 11 );
			add_action( 'bws_bkng_search_products_before_items', array( $this, 'add_to_search_form' ) );
			add_action( 'bws_bkng_pre_order_product_data', array( $this, 'add_to_order_details' ) );
			add_action( 'bws_bkng_order_product_data', array( $this, 'add_to_order_details' ) );
			add_filter( 'bws_bkng_order_meta', array( $this, 'save_with_new_order' ), 10, 2 );
			add_filter( 'bws_bkng_mail_shortcodes', array( $this, 'add_location_shortcode' ) );
			add_action( 'bws_bkng_single_order_bkng_additional_data', array( $this, 'add_to_order_edit_page' ) );
			add_filter( 'bws_bkng_update_order_result', array( $this, 'update' ), 10, 2 );
			add_filter( 'bws_bkng_messages', array( $this, 'change_on_hold_message' ) );
			add_filter( 'bws_bkng_order_pattern', array( $this, 'add_shortcode' ) );
		}

		/**
		 * Adds {location} shortcode to the mail template.
		 * @since  1.0.0
		 * @access public
		 * @param  string    $message    The mail message template
		 * @return string
		 */
		public function add_shortcode( $message ) {
			$search = '<strong>{rent_interval}</strong>.</p>';
			$replace = $search . '<p>' . __( 'Pick-up & Drop-off locations', 'bike-rental' ) . ': <strong>{locations}</strong>.</p>';
			return str_replace( $search, $replace, $message );
		}

		/**
		 * Adds {location} shortcode to the "On hold" message template
		 * during the plugin data loading after activation.
		 * @since  1.0.0
		 * @access public
		 * @param  array    $messages    The mails templates
		 * @return array
		 */
		public function change_on_hold_message( $messages ) {
			$messages['customer_on_hold'] = $this->add_shortcode( $messages['customer_on_hold'] );
			return $messages;
		}

		/**
		 * Saves the pick-up and drop-off locations to cookie
		 * @since  1.0.0
		 * @access public
		 * @param  void
		 * @return void
		 */
		public function save_cookies() {
			global $bws_bkng;

			$booking_page = $bws_bkng->is_booking_page();

			if ( ! $booking_page )
				return;

			$query   = bws_bkng_get_query();
			$session = BWS_BKNG_Session::get_instance( true );

			if ( empty( $query['search']['pickup_location'] ) )
				return;

			$old_data = (array)$session->get( 'pickup-return-locations' );
			$new_data = array( 'pickup_location' => $query['search']['pickup_location'] );

			if ( ! empty( $query['search']['return_location'] ) )
				$new_data['return_location'] = $query['search']['return_location'];

			$session->update( 'pickup-return-locations', array_merge( $old_data, $new_data ) );
		}

		/**
		 * Adds pick-up and drop-off dropdown lists of locations
		 * to the products primary search form.
		 * @since  1.0.0
		 * @access public
		 * @param  void
		 * @return void
		 */
		public function add_to_search_form() {
			global $bws_bkng;
			$attributes = $bws_bkng->get_option( 'attributes' );
			$tax_data   = array_key_exists( 'bkng_pickup_location', $attributes ) ? $attributes['bkng_pickup_location']: false;

			if ( empty( $tax_data ) || ! $bws_bkng->is_taxonomy( $tax_data['meta_type'] ) )
				return;

			$terms = $bws_bkng->get_terms( 'bkng_pickup_location', array( 'hide_empty' => false ) );

			if ( is_wp_error( $terms ) || empty( $terms ) )
				return;

			$options = array();
			$session = BWS_BKNG_Session::get_instance( true );
			$cookie  = $session->get( 'pickup-return-locations' );

			foreach( $terms as $term )
				$options[ $term->slug ] = $term->name;

			/**
			 * Show Pick-up Locations dropdown list
			 */
			$name = 'bkng_pickup_location';
			$data = array(
				'label'       => $tax_data['label'],
				'type'        => $tax_data['meta_type'],
				'value'       => ( ! empty( $cookie[ $name ] ) && array_key_exists( $cookie[ $name ], $options ) ? $cookie[ $name ] : '' ),
				'list'        => $options,
				'placeholder' => __( 'Any Locations', 'bike-rental' )
			); ?>
			<div class="bws_bkng_search_products_item"><?php bws_bkng_items_list( $name, $data ); ?></div>

			<?php $name = 'bkng_return_different_location';
			$id    = bws_bkng_sanitize_id( 'bkrntl-return-different-location' );
			$class = 'bkrntl-return-different-location';
			$attr  = empty( $cookie['return_location'] ) ? '' : 'checked="checked"';
			$after = __( 'Return at different location', 'bike-rental' );?>
			<div class="bws_bkng_search_products_item">
                <?php echo $bws_bkng->get_checkbox( compact( 'name', 'class', 'id', 'attr', 'after' ) ); ?>
            </div>

			<?php /**
			 * Show Return Locations dropdown list
			 */
			$name = 'bkng_return_location';
			$data = array(
				'label'       => __( 'Drop-off Locations', 'bike-rental' ),
				'type'        => $tax_data['meta_type'],
				'value'       => ( ! empty( $cookie[ $name ] ) && array_key_exists( $cookie[ $name ], $options ) ? $cookie[ $name ] : '' ),
				'list'        => $options,
				'placeholder' => __( 'Choose Locations', 'bike-rental' )
			); ?>
			<div class="bws_bkng_search_products_item"><?php bws_bkng_items_list( $name, $data ); ?></div>
		<?php }

		/**
		 * Displays selected pick-up and drop-off dropdown locations
		 * with the other order details on "checkout" and "thank_you" pages.
		 * @since  1.0.0
		 * @access public
		 * @param  array   $order    The order data
		 * @return void
		 */
		public function add_to_order_details( $order = '' ) {
			$data = $this->get_locations( $order );

			if ( ! $data )
				return;

			extract( $data ); ?>

			<h4><?php _e( 'Locations', 'bike-rental' ); ?></h4>
			<div>
				<span class="bws_bkng_date_label"><?php _e( 'Pick-up from', 'bike-rental' ); ?></span>
				<span><?php echo esc_html( $pickup_location->name ); ?></span>
			</div>
			<div>
				<span class="bws_bkng_date_label"><?php _e( 'Return to', 'bike-rental' ); ?></span>
				<span><?php echo esc_html( $return_location->name ); ?></span>
			</div>

		<?php }

		/**
		 * Saves selected pick-up and drop-off dropdown locations
		 * with the other order details to database.
		 * @since  1.0.0
		 * @access public
		 * @param  array        The currently managed order meta data prepared for saving to database.
		 * @param  int          The currently managed order ID.
		 * @return array        The currently managed order meta.
		 */
		public function save_with_new_order( $meta_data, $order_id ) {
			$data = $this->get_locations();

			if ( ! $data )
				return $meta_data;

			foreach( $data as $key => $term_data ) {
				$value = maybe_serialize( array( $term_data->term_id => $term_data->name ) );
				$meta_data[] = "( {$order_id}, '{$key}', '{$value}' )";
			}

			$session = BWS_BKNG_Session::get_instance( true );
			$session->remove( 'pickup-return-locations' );

			return $meta_data;
		}

		/**
		 * Adds the {location} shortcode description to the help tab
		 * on the plugin setting page (tab 'Notifications').
		 * @since  1.0.0
		 * @access public
		 * @param  array   $shortcodes    The list of available shortcodes for mail notifications
		 * @return array
		 */
		public function add_location_shortcode( $shortcodes ) {
			$shortcodes['locations'] = array(
				'description' => __( 'Adds Pickup and Return Locations of a rented product', 'bike-rental' ),
				'callback'    => array( $this, 'handle_shortcode' )
			);
			return $shortcodes;
		}

		/**
		 * The {location} shortcode handler.
		 * @since  1.0.0
		 * @access public
		 * @param  array   $mail_data    The notification data.
		 * @param  array   $order        The currently managed order data.
		 * @return array
		 */
		public function handle_shortcode( $mail_data, $order ) {

			$order_handler = BWS_BKNG_Order::get_instance();
			$order_id      = empty( $order['id'] ) ? '' : $order['id'];
			$data          = $this->get_locations( $order_handler->get( $order_id ) );

			if ( is_array( $data ) )
				extract( $data );

			$default     = __( 'Not selected', 'bike-rental' );
			$from        = __( 'pick-up from', 'bike-rental' );
			$to          = __( 'return to', 'bike-rental' );
			$from_title = empty( $pickup_location->name ) ? $default : $pickup_location->name;
			$to_title   = empty( $return_location->name ) ? $default : $return_location->name;

			return "{$from}&nbsp;{$from_title}&nbsp;-&nbsp;\t{$to}&nbsp;{$to_title}";
		}

		/**
		 * Displays selected pick-up and drop-off locations on the single order edit page in admin panel
		 * @since  1.0.0
		 * @access public
		 * @param  array   $order        The currently managed order data.
		 * @return void
		 */
		public function add_to_order_edit_page( $order ) {
			global $bws_bkng;
			$data  = $this->get_locations( $order );

			$terms = $bws_bkng->get_terms( 'bkng_pickup_location', array( 'hide_empty' => false ) );

			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$options = array();
				foreach( $terms as $term )
					$options[ $term->term_id ] = $term->name;
			}

			if ( empty( $options ) ) {
				if ( $data ) {
					extract( $data );
					$options= array(
						$pickup_location->term_id => $pickup_location->name,
						$return_location->term_id => $return_location->name,
					);
				}

			} elseif ( $data ) {
				extract( $data );

				if ( ! array_key_exists( $pickup_location->term_id, $options ) && '-1' != $pickup_location->term_id )
					$options[ $pickup_location->term_id ] = "{$pickup_location->name}&nbsp;(" . __( 'removed', 'bike-rental' ) . ')';

				if ( ! array_key_exists( $return_location->term_id, $options ) && '-1' != $return_location->term_id  )
					$options[ $return_location->term_id ] = "{$return_location->name}&nbsp;(" . __( 'removed', 'bike-rental' ) . ')';
			}

            $attr = '';
			if ( empty( $options ) || 'on_hold' != $order['status'] )
				$attr = 'disabled="disabled"';

			$options = array( '-1' => __( 'Not Selected', 'bike-rental' ) ) + $options;
			$content = '';

			$name     = "bkng_order_pickup_location";
			$selected = empty( $pickup_location->term_id ) ? '-1' : $pickup_location->term_id;
			$dropdown = $bws_bkng->get_select( compact( 'name', 'selected', 'options', 'attr' ) );
			$label    = __( 'Pick-up from', 'bike-rental' );
			$content .= "<div class=\"bws_bkng_locations\"><p>{$label}</p>{$dropdown}</div>";

			$name     = "bkng_order_return_location";
			$selected = empty( $return_location->term_id ) ? '-1' : $return_location->term_id;
			$dropdown = $bws_bkng->get_select( compact( 'name', 'selected', 'options', 'attr' ) );
			$label    = __( 'Return to', 'bike-rental' );
			$content .= "<div class=\"bws_bkng_locations\"><p>{$label}</p>{$dropdown}</div>";

			$bws_bkng->display_table_row( __( 'Pick-up & Drop-off locations', 'bike-rental' ), $content );
		}

		/**
		 * Updates pick-up and drop-off locations data in database
		 * after they were changed on the single order edit page in admin panel
		 * @since  1.0.0
		 * @access public
		 * @param  boolean|string  $result  The previous updates action result {@see BWS_BKNG_Order::update_order()}
		 * @param  array   $order           The currently managed order data.
		 * @return WP_Error|string
		 */
		public function update( $result, $order ) {
			global $wpdb;

			/* If order isn't "on_hold" */
			if ( ! isset( $_POST['bkng_order_pickup_location'] ) || ! isset( $_POST['bkng_order_return_location'] ) )
				return $result;
			
			if ( isset( $_GET['post_type'] ) ) {
				$post_type = sanitize_text_field( stripslashes( $_GET['post_type'] ) );
			}
			$table    = BWS_BKNG_DB_PREFIX . $post_type . '_orders_meta';
			$order_id = absint( $order['id'] );
			$error    = new WP_Error();

			/* If "Not selected" item has been chosen */
			foreach( array( 'pickup_location', 'return_location' ) as $key ) {

				if ( '-1' == $_POST["bkng_order_{$key}"] ) {
					$wpdb->delete(
						$table,
						array(
							'meta_key' => $key,
							'order_id' => $order_id
						)
					);

					if ( $wpdb->last_error )
						return new WP_Error( 'update_location', $wpdb->last_error );

				} else {
					$term = get_term_by( 'id', absint( $_POST["bkng_order_{$key}"] ), 'bkng_pickup_location' );

					if ( is_wp_error( $term ) || empty( $term ) )
						return new WP_Error( 'update_location', __( "Can't get location data", 'bike-rental' ) );

					if ( empty( $order[ $key ] ) ) {
						$wpdb->insert(
							$table,
							array(
								'meta_value' => maybe_serialize( array( $term->term_id => $term->name ) ),
								'meta_key' => $key,
								'order_id' => $order_id
							)
						);

					} else {
						$data = maybe_serialize( array( $term->term_id => $term->name ) );

						if ( $data == $order[ $key ] )
							continue;

						$wpdb->update(
							$table,
							array(
								'meta_value' => maybe_serialize( array( $term->term_id => $term->name ) )
							),
							array(
								'meta_key' => $key,
								'order_id' => $order_id
							)
						);
					}

					if ( $wpdb->last_error )
						return new WP_Error( 'update_location', $wpdb->last_error );
				}
			}

			return $result;
		}

		/**
		 * Fetch pick-up and drop-off locations from the order data
		 * @since  1.0.0
		 * @access public
		 * @param  array   $order     The currently managed order data.
		 * @return array   $data
		 */
		private function get_locations( $order = '' ) {
			global $bws_bkng;

			if ( $bws_bkng->is_admin() ) {
				$list = array(
					'pickup_location' => empty( $order['pickup_location'] ) ? '-1' : absint( key( $order['pickup_location'] ) ),
					'return_location' => empty( $order['return_location'] ) ? '-1' : absint( key( $order['return_location'] ) )
				);

				return $this->get_terms_by( 'id', $list, $order );
			} elseif ( empty( $order['pickup_location'] ) ) {
				$session = BWS_BKNG_Session::get_instance( true );
				$list    = $session->get( 'pickup-return-locations' );

				return empty( $list ) ? false : $this->get_terms_by( 'slug', $list, $order );

			}

			$list = array(
				'pickup_location' => key( $order['pickup_location'] ),
				'return_location' => empty( $order['return_location'] ) ? key( $order['pickup_location'] ) : key( $order['return_location'] )
			);

			return $this->get_terms_by( 'slug', $list, $order );
		}

		private function get_terms_by( $field, $list, $order = '' ) {
			$data        = array( 'pickup_location' => '', 'return_location' => '' );
			$pickup_term = $list['pickup_location'];
			foreach( $data as $key => $value ) {

				if ( $key == 'return_location' && ( empty( $list[ $key ] ) || $list[ $key ] == $pickup_term ) ) {
					$data[ $key ] = $data['pickup_location'];
					continue;
				}
				$identifier = $key == 'return_location' ? $list[ $key ] : $pickup_term;
				$data[ $key ] = get_term_by( $field, $identifier, 'bkng_pickup_location' );
				if ( ! $data[ $key ] ) {
					$data[ $key ]          = new stdClass();
					$data[ $key ]->term_id = empty( $order[ $key ] ) ? '-1' : key( $order[ $key ] );
					$data[ $key ]->name    = empty( $order[ $key ] ) ? __( 'Not selected', 'bike-rental' ) : $order[ $key ][ $data[ $key ]->term_id ];
				}
			}

			return $data;
		}
	}
}

new BKRNTL_Locations();