<?php /**
 * Contains the functions for adaption the Booking Core for plugin
 * @since Bike Rental 1.0.0
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( ! class_exists( 'BKRNTL_Booking_Adapter' ) ) {

	class BKRNTL_Booking_Adapter {

		/**
		 * The class constructor.
		 * @since  1.0.0
		 * @access public
		 * @param  void
		 * @return void
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'plugin_init' ) );
			add_action( 'admin_init', array( $this, 'add_meta_boxes' ) );
			add_filter( 'bws_bkng_bkrntl_demo_data', array( $this, 'add_demo_data' ) );
			add_action( 'bws_bkng_checkout_form_after_personal_info', array( $this, 'add_age_dropdown' ), 10, 2 );
			add_filter( 'bws_bkng_billing_data', array( $this, 'add_user_age' ) );
			add_filter( 'bws_bkng_order_meta', array( $this, 'save_user_age' ), 10, 4 );
			add_filter( 'bws_bkng_order_meta', array( $this, 'save_order_details' ), 10, 2 );
			add_action( 'bws_bkng_after_personal_info', array( $this, 'display_user_age_in_front' ) );
			add_action( 'bws_bkng_single_order_customers_details_after_personal_info', array( $this, 'display_user_age_in_admin' ) );
			add_filter( 'bws_bkng_replace_mail_shortcode', array( $this, 'send_user_age' ), 10, 4 );
			add_filter( 'bws_bkng_meta_tabs', array( $this, 'meta_tabs' ) );

			// add_action( 'save_post_bws_extra', array( $this, 'save_bike_extra_relations' ), 10, 2 );
		}

		/**
		 * Adds products post type and taxomonies during after the plugin activation
		 * @since  1.0.0
		 * @access public
		 * @param  void
		 * @return void
		 */
		public function plugin_init() {
			global $bws_bkng;
			$plugin_db_version = get_option( $bws_bkng->plugin_prefix . '_plugin_db_version' );

			if ( $bws_bkng->get_db_version() > $plugin_db_version ) {
				$bws_bkng->data_loader->create_attributes_db_tables();
				$bws_bkng->data_loader->create_locations_db_tables();
				$this->create_bike_extra_table();

				update_option( $bws_bkng->plugin_prefix . '_plugin_db_version', $bws_bkng->get_db_version() );
			}
			$bws_bkng->data_loader->add_data_attributes();
			$bws_bkng->data_loader->register_booking_objects();
			$this->add_custom_taxonomy_fields();
			if ( ! is_admin() ) {
				/* add template for booking pages */
				add_action( 'template_include', array( $this, 'template_include' ) );
			}
		}

		private function create_bike_extra_table(){
			global $wpdb;
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			$sql = 'CREATE TABLE IF NOT EXISTS `' . BWS_BKNG_DB_PREFIX . 'bws_extra_bws_bike_relations` (
				`bike_extra_id` BIGINT(25) NOT NULL AUTO_INCREMENT,
				`bws_extra_id` BIGINT(25) NOT NULL,
				`bws_bike_id` BIGINT(25) NOT NULL,
				UNIQUE KEY ( `bike_extra_id` )
				);';
			/* call dbDelta */
			dbDelta( $sql );
		}

		private function add_custom_taxonomy_fields() {
			add_action( 'admin_enqueue_scripts', array( $this, 'load_media' ) );
			add_action( 'admin_footer', array ( $this, 'add_script' ) );
		}

		public function add_amenity_icon_field( $term ){
			global $bws_bkng;?>
			<tr class="form-field term-icon-wrap">
				<th scope="row">
					<label for="icon"><?php _e( 'Icon', 'bike-rental' ); ?></label>
				</th>
				<td>
					<?php $image_id = get_term_meta ( $term->term_id, $bws_bkng->plugin_prefix . '_amenity-icon-id', true ); ?>
       		<input type="hidden" id="amenity-icon-id" name="amenity-icon-id" class="custom_media_url" value="<?php echo esc_attr( $image_id ); ?>" />
					<div id="amenity-icon-wrapper">
						<?php if ( $image_id ) {
							echo wp_get_attachment_image ( $image_id, 'thumbnail' );
						} ?>
					</div>
					<p>
						<input type="button" class="button button-secondary" id="amenity_icon_add" name="amenity_icon_add" value="<?php _e( 'Add Icon', 'bike-rental' ); ?>" />
						<input type="button" class="button button-secondary <?php if ( ! $image_id ) echo 'hidden'; ?>" id="amenity_icon_remove" name="amenity_icon_remove" value="<?php _e( 'Remove Icon', 'bike-rental' ); ?>" />
					</p>
				</td>
			</tr>
		<?php }

		public function load_media() {
			wp_enqueue_media();
		}

		public function add_script() { ?>
			<script>
			( function($) {
				$( document ).ready( function() {
					if ( typeof wp !== 'undefined' && wp.media && wp.media.editor) {
						var frame;
						$( '#amenity_icon_add' ).on( 'click', function( event ) {
							event.preventDefault();
							if ( frame ) {
								frame.open();
								return;
							}
							frame = wp.media({
								title: "<?php _e( 'Select or Upload Icon For Amenity', 'bike-rental' );?>",
								button: {
								text: "<?php _e( 'Use this icon', 'bike-rental' ); ?>"
								},
								multiple: false
							});
							frame.on( 'select', function() {
								var attachment = frame.state().get( 'selection' ).first().toJSON();
								$( '#amenity-icon-id' ).val( attachment.id );
								$( '#amenity-icon-wrapper' ).html( '<img src="' + attachment.url + '" alt="" style="max-width:100%;"/>' );
								$( '#amenity_icon_remove' ).removeClass( 'hidden' );
							});
							frame.open();
						});

						$( '#amenity_icon_remove' ).on( 'click', function( event ) {
								event.preventDefault();
								$( '#amenity-icon-id' ).val( '' );
								$( '#amenity-icon-wrapper' ).html( '' );
								$( '#amenity_icon_remove' ).addClass( 'hidden' );
								return false;
						});
					}
				});
			})(jQuery);
			</script>
		<?php }

		public function save_amenity_icon_field( $term_id ){
			global $bws_bkng;
			if ( ! isset( $_POST['amenity-icon-id'] ) || ! current_user_can('edit_term', $term_id) ) {
				return;
			}
			if( isset( $_POST['amenity-icon-id'] ) && '' !== $_POST['amenity-icon-id'] ){
				$image = intval( $_POST['amenity-icon-id'] );
				update_term_meta ( $term_id, $bws_bkng->plugin_prefix . '_amenity-icon-id', $image, true );
			} else {
				delete_term_meta( $term_id, $bws_bkng->plugin_prefix . '_amenity-icon-id' );
			}

			return $term_id;
		}

		/**
		* Load a template. Handles template usage so that plugin can use own templates instead of the themes.
		*
		* Templates are in the 'templates' folder.
		* overrides in /{theme}/bws-templates/ by default.
		* @param mixed $template
		* @return string
		*/
		public function template_include( $template ) {
			global $bws_bkng, $wp_query;

			if ( function_exists( 'is_embed' ) && is_embed() ) {
				return $template;
			}

			$post_type = get_post_type();

			if ( is_single() && 'bws_bike' == $post_type ) {
				$file = 'bws-bkng-single-bike.php';
			} elseif ( get_queried_object() && $bws_bkng->get_option( 'checkout_page' ) === get_queried_object()->ID ) {
				$file = 'bws-bkng-checkout.php';
			} elseif ( get_queried_object() && $bws_bkng->get_option( 'thank_you_page' ) === get_queried_object()->ID ) {
				$file = 'bws-bkng-thank-you.php';
			} elseif ( get_queried_object() && $bws_bkng->get_post_type_option( 'bws_bike', 'products_page' ) === get_queried_object()->ID ) {
				$file = 'bws-bkng-bike-model-select.php';
			} elseif ( is_search() ) {
				$file = 'bws-bkng-search-results-template.php';
			}

			if ( isset( $file ) ) {
				$find = array( $file, 'bws-templates/' . $file );
				$template = locate_template( $find );

				if ( ! $template ) {
					$template = untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' . $file;
				}
			}

			return $template;
		}

		public function add_meta_boxes() {
			global $wpdb, $bws_bkng;

			$bws_bkng->data_loader->register_metaboxes();
			new BWS_BKNG_Custom_Type_Metabox( array(
				'post_type'				=> 'bws_extra',
				'post_type_display'		=> 'bws_bike',
				'metabox_title'			=> __( 'Bikes', 'bike-rental' ),
				'post_relations_table'	=> BWS_BKNG_DB_PREFIX . 'bws_extra_bws_bike_relations',
				'not_fount_text'		=> __( 'No Bike Set', 'bike-rental' ),
				'add_new_text'			=> __( 'Add New Bike', 'bike-rental' ),
				'add_new_link'			=> 'post-new.php?post_type=bws_bike'
			) );
		}

		/**
		 * Fetch the list of demo-data to be installed
		 * @since  1.0.0
		 * @access public
		 * @param  array   $demo_data
		 * @return array
		 */
		public function add_demo_data( $demo_data ) {
			$demo_data = array_merge( $demo_data, (array)include 'demo-data.php' );
			return $demo_data;
		}

		/**
		 * Change default products' post type data
		 * @since  1.0.0
		 * @access public
		 * @param  array   $data
		 * @return array
		 */
		public function change_post_type_register_data( $data ) {
			$data['labels']['menu_name'] = __( 'Bike Rental', 'bike-rental' );
			return $data;
		}

		public function get_available_extra( $bike_id ) {
			global $wpdb, $wp_query;
			$old_wp_query = $wp_query;

			$all_bike_extra_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT `bws_extra_id` FROM `' . BWS_BKNG_DB_PREFIX . 'bws_extra_bws_bike_relations` WHERE bws_bike_id = %d', $bike_id ) );

			$all_bike_extra = get_posts( array( 'post_type' => 'bws_extra','posts_per_page' => -1, 'include' => $all_bike_extra_ids ) );
			$wp_query = $old_wp_query;
			return $all_bike_extra;
		}

		public function get_available_extra_ids( $bike_id ) {
			global $wpdb;
			$like = '%' . $wpdb->esc_like( $bike_id ) . '%';
			$all_bike_extra_ids = $wpdb->get_col( $wpdb->prepare( 'SELECT `post_id` FROM `' . $wpdb->postmeta. '` WHERE `meta_key` = "bkrntl_bike_extra" AND `meta_value` LIKE %s', $like ) );
			return $all_bike_extra_ids;
		}

		public function get_attribute_values( $post_type, $attribute_name ) {
			global $wpdb;

			$field_values_table      = BWS_BKNG_DB_PREFIX . $post_type . '_field_values';
            $field_ids_table         = BWS_BKNG_DB_PREFIX . $post_type . '_field_ids';
            $field_post_values_table = BWS_BKNG_DB_PREFIX . $post_type . '_field_post_data';

		    $values = $wpdb->get_col(
                $wpdb->prepare(
                    'SELECT ' . $field_values_table . '.value_name FROM ' . $field_values_table . '
			        INNER JOIN ' . $field_ids_table . ' ON ' . $field_ids_table . '.field_id = ' . $field_values_table . '.field_id
			        INNER JOIN ' . $field_post_values_table . ' ON ' . $field_values_table . '.value_id = ' . $field_post_values_table . '.post_value
			        AND ' . $field_ids_table . '.field_slug = %s
					GROUP BY ' . $field_values_table . '.value_name;',
                    $attribute_name
                )
            );

		    if( ! empty( $values ) ) {
			    return $values;
            } else {
		        return false;
            }
        }

        public function is_countable( $post_type, $post = null ) {
	        global $wpdb;

	        $field_ids_table = BWS_BKNG_DB_PREFIX . $post_type . '_field_ids';
	        $field_post_data_table = BWS_BKNG_DB_PREFIX . $post_type . '_field_post_data';

	        $is_countable = $wpdb->get_var(
                $wpdb->prepare(
                    'SELECT ' . $field_post_data_table . '.post_value FROM ' . $field_post_data_table . '
                    INNER JOIN ' . $field_ids_table . ' ON ' . $field_ids_table . '.field_id = ' . $field_post_data_table . '.field_id
                    AND ' . $field_ids_table . '.field_slug = "bkrntl_quantity_available"
                    AND ' . $field_post_data_table . '.post_id = %d;',
                    $post
                )
            );

	        return null == $is_countable ? false : true;
        }

		public function max_quantity( $post_type, $post = null ) {
			global $wpdb;

			$field_ids_table = BWS_BKNG_DB_PREFIX . $post_type . '_field_ids';
			$field_post_data_table = BWS_BKNG_DB_PREFIX . $post_type . '_field_post_data';

			$max_quantity = $wpdb->get_var(
			    $wpdb->prepare(
                    'SELECT ' . $field_post_data_table . '.post_value FROM ' . $field_post_data_table . '
			        INNER JOIN ' . $field_ids_table . ' ON ' . $field_ids_table . '.field_id = ' . $field_post_data_table . '.field_id
			        AND ' . $field_ids_table . '.field_slug = "bkrntl_quantity"
			        AND ' . $field_post_data_table . '.post_id = %d;',
                    $post
                )
            );
			return $max_quantity;
		}

        public function bws_bkng_bws_bike_join( $join ) {
	        global $wpdb;

	        $field_post_data_table = BWS_BKNG_DB_PREFIX . 'bws_bike_field_post_data';
	        $field_ids_table = BWS_BKNG_DB_PREFIX . 'bws_bike_field_ids';

            if ( isset( $_GET['price'] ) || ( isset( $_GET['sort'] ) && 'price' === $_GET['sort'] ) ) {
                $join .= ' INNER JOIN ' . $field_post_data_table . ' ON ' . $wpdb->prefix . 'posts.ID = ' . $field_post_data_table . '.post_id
                           INNER JOIN ' . $field_ids_table . ' ON ' . $field_post_data_table . '.field_id = '. $field_ids_table . '.field_id ';
            }

            return $join;
        }
		public function bws_bkng_bws_bike_where( $where ) {
			global $wpdb;
			$field_values_table = BWS_BKNG_DB_PREFIX . 'bws_bike_field_values';
			$field_post_data_table = BWS_BKNG_DB_PREFIX . 'bws_bike_field_post_data';
			$field_ids_table = BWS_BKNG_DB_PREFIX . 'bws_bike_field_ids';

			if ( isset( $_GET['price'] ) || ( isset( $_GET['sort'] ) && 'price' === $_GET['sort'] ) ) {
				$where .= ' AND ' . $field_ids_table . '.field_name = \'Price\'';
			}
			if ( isset( $_GET['intended_for'] ) && 'default' !== $_GET['intended_for'] ){
				$where .= ' AND '. $wpdb->prefix . 'posts.ID IN ( SELECT ' . $field_post_data_table . '.post_id FROM ' . $field_post_data_table . '
				  INNER JOIN ' . $field_values_table . ' ON ' . $field_post_data_table . '.post_value = '. $field_values_table . '.value_id
				  AND ' . $field_values_table . '.value_name = \'' .  sanitize_text_field( stripslashes( $_GET['intended_for'] ) ) . '\') ';
            }
			if ( isset( $_GET['bike_brand'] ) ){
				$where .= ' AND '. $wpdb->prefix . 'posts.ID IN ( SELECT ' . $field_post_data_table . '.post_id FROM ' . $field_post_data_table . '
				  INNER JOIN ' . $field_values_table . ' ON ' . $field_post_data_table . '.post_value = '. $field_values_table . '.value_id
				  AND ' . $field_values_table . '.value_name = \'' . sanitize_text_field( stripslashes( $_GET['bike_brand'] ) ) . '\') ';
			}

			return $where;
		}
		public function bws_bkng_bws_bike_groupby( $groupby ) {
			global $wpdb;

			if ( isset( $_GET['sort'] ) && 'price' === $_GET['sort'] ) {
				$groupby = $wpdb->posts . '.ID';
			}

			return $groupby;
		}
		public function bws_bkng_bws_bike_orderby( $orderby ){
			$field_post_data_table = BWS_BKNG_DB_PREFIX . 'bws_bike_field_post_data';

			if ( isset( $_GET['price'] ) ) {
				$orderby = '' . $field_post_data_table . '.post_value ' .  sanitize_text_field( stripslashes( $_GET['price'] ) );
			}
			if ( isset( $_GET['sort'] ) && 'price' === $_GET['sort'] ) {
				$orderby = '' . $field_post_data_table . '.post_value DESC';
			}

			return $orderby;
		}
		public function meta_tabs( $tabs ){
		    global $post;
		    if( isset( $post ) && 'bws_extra' == get_post_type( $post->ID ) ) {
		        foreach ( $tabs as $key => $tab )
			    if( 'general' != $key ) {
			        unset( $tabs[ $key ] );
                }
            }
		    return $tabs;
        }


		/**
		 * Adds dropdown list to select customer's age
		 * @since  1.0.0
		 * @access public
		 * @param  array   $billing_data     Customer's billing data.
		 * @param  array   $errors           The list of errors.
		 * @return void
		 */
		public function add_age_dropdown( $billing_data, $errors ) {
			$options  = range( 16, 100 );
			$selected = empty( $billing_data['user_age'] ) ? 16 : absint( $billing_data['user_age'] ); ?>
			<p<?php echo bws_bkng_is_error( 'user_age', $errors ) ? ' class="bws_bkng_error_input_wrap"' : ''; ?>>
				<label><?php _e( 'Age', 'bike-rental' ); ?></label><br/>
				<select name="bkng_billing_data[user_age]">
					<?php foreach( $options as $option ) {
						$attr = $selected == $option ? ' selected="selected"' : ''; ?>
						<option value="<?php echo esc_attr( $option ); ?>"<?php echo esc_attr( $attr ); ?>><?php echo esc_html( $option ); ?></option>
					<?php } ?>
				</select>
			</p>
		<?php }

		/**
		 * Adds the customer selected age to the order billings data
		 * @since  1.0.0
		 * @access public
		 * @param  array   $billing_data     Customer's billing data.
		 * @return array   $billing_data     Customer's billing data.
		 */
		public function add_user_age( $billing_data ) {
			if ( empty( $_REQUEST['bkng_billing_data']['user_age'] ) ) {
				$billing_data['user_age'] = 16;
			} else {
				$age = absint( $_REQUEST['bkng_billing_data']['user_age'] );
				$billing_data['user_age'] = empty( $age ) || ! in_array( $age, range( 16, 100 ) ) ? 16 : $age;
			}
			return $billing_data;
		}

		/**
		 * Prepares the customer selected age for saving to the database
		 * @since  1.0.0
		 * @access public
		 * @param  array   $order_meta       The order meta data.
		 * @param  int     $order_id         The order ID.
		 * @param  array   $products         The list of orderd products.
		 * @param  array   $billing_data     Customer's billing data.
		 * @return array
		 */
		public function save_user_age( $order_meta, $order_id, $products, $billing_data ) {
			$order_meta[] = "( {$order_id}, 'user_age', '{$billing_data['user_age']}' )";
			return $order_meta;
		}

		/**
		 * Saves order options like pedals type, bike size e.t.c to DB
		 * @since  1.0.0
		 * @access public
		 * @param  array   $order_meta       The order metadata.
		 * @param  int     $order_id         The order ID.
		 * @return array
		 */
		public function save_order_details( $order_meta, $order_id ) {
			$session = BWS_BKNG_Session::get_instance( true );
			$options = $session->get( 'order_options' );

			if ( empty( $options ) ) {
				return $order_meta;
			}

			foreach ( $options as $key => $option ) {
				if ( empty( $option ) ) {
					continue;
				}
				$order_meta[] = "( " . $order_id . ", '" . $key . "', '" . $option . "' )";
			}

			return $order_meta;
		}

		/**
		 * Displays the customer age on the "Thank you" page
		 * @since  1.0.0
		 * @access public
		 * @param  array   $order_data     The order meta data.
		 * @return void
		 */
		public function display_user_age_in_front( $order_data ) { ?>
			<p>
				<strong><?php _e( 'Age', BWS_BKNG_TEXT_DOMAIN ); ?>:</strong>
				<span class=""><?php echo esc_html( $order_data['user_age'] ); ?></span>
			</p>
		<?php }

		/**
		 * Displays the customer age on the single order edit page
		 * @since  1.0.0
		 * @access public
		 * @param  array   $order_data     The order meta data.
		 * @return void
		 */
		public function display_user_age_in_admin( $order_data ) {
			global $bws_bkng;

			$bws_bkng->display_table_row( __( 'Age', 'bike-rental' ), $order_data['user_age'] );
		}

		/**
		 * Adds the customer selected age to the e-mail
		 * @since  1.0.0
		 * @access public
		 * @param  string  $content       The content generated according to the shortcode.
		 * @param  string  $shortcode     The shortcode slug.
		 * @param  array   $mail_data     The mail data to be sent.
		 * @param  array   $order_data    The currentlt managed order data.
		 * @return string                 The shortcode content.
		 */
		public function send_user_age( $content, $shortcode, $mail_data, $order_data ) {
			if ( 'billing_details' !== $shortcode )
				return $content;

			$label = __( 'Age', 'bike-rental' );
			$user_age_row =
				"<tr>
					<td><strong>{$label}</strong></td>
					<td>{$order_data['user_age']}</td>
				</tr>";

			return preg_replace( "/<table>/", "<table>{$user_age_row}", $content );
		}

//		public function save_bike_extra_relations( $post_id, $post ){
//			global $wpdb;
//			if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
//				return $post_id;
//			}
//			if( isset( $_POST['bkrntl_bws_extra_id'] ) ) {
//				$current_bike_extra = (array) $wpdb->get_col(
//                    $wpdb->prepare(
//                        'SELECT `bike_id`
//                        FROM `' . BWS_BKNG_DB_PREFIX . 'bws_bike_extra_relations`
//                        WHERE `extra_id` = %d',
//                        $post_id
//                    )
//				);
//                $bike_ids = array_map( 'intval', $_POST['bkrntl_bws_extra_id'] );
//				foreach( $bike_ids as $value ) {
//					if( false === in_array( $value, $current_bike_extra ) ) {
//						$wpdb->insert(
//							BWS_BKNG_DB_PREFIX . 'bws_bike_extra_relations',
//							array(
//								'extra_id'  => $post_id,
//								'bike_id' => $value
//							),
//							array(
//								'%d',
//								'%d'
//							)
//						);
//					}
//				}
//			} else {
//				$wpdb->delete(
//					BWS_BKNG_DB_PREFIX . 'bws_bike_extra_relations',
//					array(
//						'extra_id' => $post_id
//					),
//					array( '%d' )
//				);
//			}
//			return $post_id;
//		}
	}
}

global $bws_booking_adapter;
$bws_booking_adapter = new BKRNTL_Booking_Adapter();
