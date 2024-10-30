<?php
/**
 * Manage the products additional data
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Post_Metabox' ) )
	return;

class BWS_BKNG_Post_Metabox {

	private static $instance = NULL;

	/**
	 * The primary product data - an instance of the class WP_Post
	 * @since  0.1
	 * @access private
	 * @var object
	 */
	private $post;

	/**
	 * The currently managed product data - an instance of the class WP_Post.
	 * If the currently managed product is primary then it contains the same data as self::$post
	 * @since  0.1
	 * @access private
	 * @var object
	 */
	private $variation;
	public $post_type;

	/**
	 * GET-parameters, are used to manage the products data
	 * @since  0.1
	 * @access public
	 * @var string
	 */
	public static $name           = 'bkng_post_meta';
	public static $action_slug    = 'bkng_variation_action';
	public static $message_slug   = 'bkng_message';
	public static $error_slug     = 'bkng_error';
	public static $variation_slug = 'bkng_variation';
	public static $nonce_slug     = 'bkng_nonce';

	/**
	 * Class constructor
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function __construct() {
		global $bws_bkng;

		$this->post_type = get_post_type();
		if( false === $this->post_type && isset( $_GET['post_type'] ) ) {
			$this->post_type = sanitize_text_field( stripslashes( $_GET['post_type'] ) );
		}
		/**
		 * Init the products additional data handler in order to detect the
		 * save the event of saving products data and handle it
		 */
		if (
			! empty( $_REQUEST[ $this->action_slug ] )
			&& wp_verify_nonce( $_REQUEST[ $this->nonce_slug ], $this->action_slug )
		) {
			$this->process_actions();
		}
		if ( $bws_bkng->allow_variations ) {
			add_action( 'transition_post_status', array( $this, 'change_children_status' ), 10, 3 );
		}

		add_filter( 'redirect_post_location', array( $this, 'add_redirect_param' ), 1, 2 );

		if ( $bws_bkng->is_booking_page() ) {
			add_action( 'admin_notices', array( $this, 'display_notices' ) );
		}
	}

	/**
	 * Fetch the class instance
	 * @since    0.1
	 * @access   public
	 * @static
	 * @param    void
	 * @return   object    An instance of the current class
	 */
	public static function get_instance() {

		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Handle the actions
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return array|void|bool   The list of parameters for redirect
	 */
	public function process_actions() {
		global $bws_bkng, $wpdb;
		/* if the action fires via JS */
		if ( isset( $_POST['post_ID'] ) ) {
			$primary_id = absint( $_POST['post_ID'] );
		/* if JS is disabled or broken  */
		} elseif ( isset( $_GET['post'] ) ) {
			$primary_id = absint( $_GET['post'] );
		} else {
			$primary_id = 0;
		}
		$variation_id = empty( $_REQUEST[ $this->variation_slug ] ) ? $primary_id : absint( $_REQUEST[ $this->variation_slug ] );

		if ( empty( $variation_id ) || empty( $primary_id ) )
			return;

		if ( $bws_bkng->allow_variations ) {
			switch( $_REQUEST[ $this->action_slug ] ) {
				case 'add_new':
					$this->variation = $this->insert_variation( $primary_id );

					$category = wp_get_post_terms( $primary_id, BWS_BKNG_CATEGORIES );

					if ( ! empty( $category[0]->term_id ) )
						wp_set_object_terms( $this->variation->ID, $category[0]->term_id, BWS_BKNG_CATEGORIES );

					update_post_meta(
						$this->variation->ID,
						'bkng_sku',
						$this->get_unique_sku( "#{$this->variation->ID}", $this->variation->ID )
					);

					if ( is_wp_error( $this->variation ) )
						$to_add = array( $this->error_slug => $_REQUEST[ $this->action_slug ] );
					else
						$to_add = array( $this->message_slug => $_REQUEST[ $this->action_slug ] );
					break;
				case 'copy':
					$this->variation = $this->insert_variation( $primary_id );

					if ( is_wp_error( $this->variation ) ) {
						$to_add = array( $this->error_slug => $_REQUEST[ $this->action_slug ] );
						break;
					}
					/* copy meta-data */
					$wpdb->query(
						"INSERT INTO `{$wpdb->postmeta}`
						( `post_id`, `meta_key`, `meta_value` )
						( SELECT {$this->variation->ID}, `meta_key`, `meta_value` FROM `{$wpdb->postmeta}` WHERE `post_id`={$variation_id} AND `meta_key` LIKE '%bkng%' );"
					);

					/* save the product with unique SKU */
					update_post_meta(
						$this->variation->ID,
						'bkng_sku',
						$this->get_unique_sku( "#{$this->variation->ID}", $this->variation->ID )
					);

					/* copy taxonomy terms bindings */
					$wpdb->query(
						"INSERT INTO `{$wpdb->prefix}term_relationships`
						( `object_id`, `term_taxonomy_id`, `term_order` )
						( SELECT {$this->variation->ID}, `term_taxonomy_id`, `term_order` FROM `{$wpdb->prefix}term_relationships` WHERE `object_id`={$variation_id} );"
					);

					clean_object_term_cache( $variation_id, BWS_BKNG_VARIATION );

					$to_add = array( $this->message_slug => $_REQUEST[ $this->action_slug ] );

					break;
				case 'delete':
					/* prevent the removing of the main product */
					if ( $variation_id == $primary_id )
						return;
					$result = wp_delete_post( $variation_id, true );
					$key    = $result ? $this->message_slug : $this->error_slug;
					$to_add = array( $key => $_REQUEST[ $this->action_slug ] );
					$to_remove = array( $this->variation_slug, $this->error_slug, $this->action_slug, $this->nonce_slug, $this->message_slug );
					break;
				default:
					$to_add = array();
					break;
			}

			if ( ! empty( $to_add ) ) {

				if ( 'delete' != $_REQUEST[ $this->action_slug ] && ! is_wp_error( $this->variation ) )
					$to_add[ $this->variation_slug ] = $this->variation->ID;

				$sendback = wp_get_referer();

				if ( ! empty( $to_remove ) )
					$sendback  = remove_query_arg( $to_remove, wp_get_referer() );

				$sendback = add_query_arg( $to_add, $sendback );
				wp_redirect( $sendback );
				exit();
			}
		}

		return empty( $to_add ) ? false : $to_add;
	}

	/**
	 * Saves the product additional attributes
	 * @see    BWS_BKNG_Admin::__construct()
	 * @since  0.1
	 * @access public
	 * @param  int        $post_id     The ID of currently managed product
	 * @param  object     $post        The ID of currently managed product - an instance of the class WP_Post
	 * @return int        $post_id
	 */
	public static function save_post( $post_id, $post ) {
		global $bws_bkng, $wpdb;

		$instance = BWS_BKNG_Post_Metabox::get_instance();

		$bws_post_types = $bws_bkng->get_post_types();

		/**
		 * Don't do anything if:
		 * - there is a revision
		 * - there is not enough data
		 * - it is not edit product's page
		 * - they want to delete some variations
		 */
		if (
			wp_is_post_revision( $post_id ) ||
			empty( $_POST[ $instance->name ] )  ||
			! in_array( $post->post_type, $bws_post_types )
		) {
			return $post_id;
		}

		$edited_id = empty( $_POST['bkng_edited_variation'] ) ? $post_id : absint( $_POST['bkng_edited_variation'] );
		$edited_id = empty( $edited_id ) ? $post_id : $edited_id;

		/* New functional for save post data to field_post_data db table */
		$wpdb->delete( BWS_BKNG_DB_PREFIX . $instance->post_type . '_field_post_data', array( 'post_id' => $post_id ) );

		if ( ! empty( $_POST[ $bws_bkng->plugin_prefix . '_attribute'] ) ) {
			foreach ( $_POST[ $bws_bkng->plugin_prefix . '_attribute'] as $key => $values ) {
				if ( ! is_array ( $values ) ) {
					$wpdb->insert(
						BWS_BKNG_DB_PREFIX . $instance->post_type . '_field_post_data',
						array(
							'field_id'		=> absint( $key ),
							'post_id'		=> $post_id,
							'post_value'	=> sanitize_text_field( stripslashes( $values ) )
						),
						array(
							'%d',
							'%d',
							'%s'
						)
					);
				} else {
					foreach ( $values as $value ) {
						$wpdb->insert(
							BWS_BKNG_DB_PREFIX . $instance->post_type . '_field_post_data',
							array(
								'field_id'		=> absint( $key ),
								'post_id'		=> $post_id,
								'post_value'	=> sanitize_text_field( stripslashes( $value ) )
							),
							array(
								'%d',
								'%d',
								'%s'
							)
						);
					}
				}
			}
		}
		if ( ! empty( $_POST['bkng_post_meta'] ) ) {
			foreach ( $_POST['bkng_post_meta'] as $key => $values ) {
				if ( 'bws_bkng_products_images' === $key ) {
					continue;
				}

                $values = is_array( $values ) ? sanitize_text_field( maybe_serialize( $values ) ) : sanitize_text_field( $values );
				$wpdb->insert(
					BWS_BKNG_DB_PREFIX . $instance->post_type . '_field_post_data',
					array(
						'field_id'		=> absint( $key ),
						'post_id'		=> $post_id,
						'post_value'	=> $values
					),
					array(
						'%d',
						'%d',
						'%s'
					)
				);
			}
		}

		if ( ! empty( $_POST[ $bws_bkng->plugin_prefix . '_locations'] ) ) {
			if ( isset( $_POST[ $bws_bkng->plugin_prefix . '_locations_id' ] ) ) {
				$wpdb->update(
					BWS_BKNG_DB_PREFIX . 'post_location',
					array(
						'post_id'			=> $post_id,
						'location_id'	    => absint( $_POST[ $bws_bkng->plugin_prefix . '_locations'] ),
						'location_post_type' => $post->post_type
					),
					array(
						'id' => absint( $_POST[ $bws_bkng->plugin_prefix . '_locations_id' ] )
					),
					array(
						'%d',
						'%d',
						'%s'
					),
					array( '%d' )
				);
			} else {
				$wpdb->insert(
					BWS_BKNG_DB_PREFIX . 'post_location',
					array(
						'post_id'			=> $post_id,
						'location_id'	    => absint( $_POST[ $bws_bkng->plugin_prefix . '_locations'] ),
						'location_post_type' => $post->post_type
					),
					array(
						'%d',
						'%d',
						'%s'
					)
				);
			}
		}

		/**
		 * Save rental point/agency
		 */
		if (
			$bws_bkng->get_post_type_option( get_post_type(), 'enable_agencies' ) &&
			isset( $_POST[ $instance->name ][ BWS_BKNG_AGENCIES ] )
		) {
			/* get all taxonomy terms for the given post */
			$post_terms = wp_get_post_terms( $edited_id, BWS_BKNG_AGENCIES );
			/* get the list of terms slugs which are associated with the the given post */
			$old_points = $bws_bkng->array_map( 'array_column', $post_terms, 'slug' );
			$new_point  = sanitize_text_field( stripslashes( $_POST[ $instance->name ][ BWS_BKNG_AGENCIES] ) );

			/* If we change rental point or if it is the new post */
			if ( false === array_search( $new_point, $old_points ) ) {
				wp_remove_object_terms( $edited_id, $old_points, BWS_BKNG_AGENCIES );
				wp_set_post_terms( $edited_id, $new_point, BWS_BKNG_AGENCIES );
			}
		}

		/*
		 * Save images
		 */
		$gallery = new BWS_BKNG_Image_Gallery( $post_id, $instance->name );
		$gallery->save_images();

		/*
		 * Forminng the necessary redirect parameters to display service message or switch to another variation
		 */
		if ( session_id() || session_start() ) {
			$params = array();
			if ( empty( $redirect_params[ $instance->variation_slug ] ) )
				$params[ $instance->variation_slug ] = empty( $_POST['bkng_variation'] ) ? $edited_id : absint( $_POST['bkng_variation'] );
			else
				$params[ $instance->variation_slug ] = $redirect_params[ $instance->variation_slug ];

			if ( ! empty( $redirect_params[ $instance->message_slug ] ) )
				$params[ $instance->message_slug ] = $redirect_params[ $instance->message_slug ];

			if ( ! empty( $redirect_params[ $instance->error_slug ] ) )
				$params[ $instance->error_slug ] = $redirect_params[ $instance->error_slug ];

			$_SESSION['redirect_params'] = $params;
		}

		return $post_id;
	}

	public function change_children_status( $new_status, $old_status, $post ) {
		global $bws_bkng;
		$bws_post_types = $bws_bkng->get_post_types();
		$current_post_type = get_post_type( $post );

		if ( ! in_array( $current_post_type, $bws_post_types ) ) {
			return;
		}

		if ( $new_status != $old_status )
			$this->update_variations( $post->ID, 'update_status', $new_status );
	}

	/**
	 * Removes all variations during the product deleting
	 * @see
	 * @since  0.1
	 * @access public
	 * @param  int       $post_id    The  ID of the primary post
	 * @return void
	 */
	public function delete_post( $post_id ) {
		global $bws_bkng;
		$bws_post_types = $bws_bkng->get_post_types();
		$current_post_type = get_post_type( $post_id );

		if ( ! in_array( $current_post_type, $bws_post_types ) ) {
			return;
		}

		$this->update_variations( $post_id, 'delete' );

	}

	/**
	 * Fetch the unique SKU for the product
	 * @since  0.1
	 * @access public
	 * @param  string        $sku         Current raw SKU
	 * @param  int           $post_id     The product ID
	 * @return string        $new_sku     SKU
	 */
	public function get_unique_sku( $sku, $post_id ) {
		global $wpdb;

		$sku     = esc_sql( $sku );
		$post_id = absint( $post_id );

		$existed = $wpdb->get_row( "SELECT `meta_id` FROM `{$wpdb->postmeta}` WHERE `meta_key`='bkng_sku' AND `meta_value`='{$sku}' AND `post_id`<>{$post_id} LIMIT 1;" );

		if ( ! $existed )
			return $sku;

		$origin  = preg_replace( '/^(.*)?(\-[\d]+)$/', '$1', $sku );
		$existed = $wpdb->get_col( "SELECT `meta_value` FROM `{$wpdb->postmeta}` WHERE `meta_key`='bkng_sku' AND `meta_value` LIKE '{$origin}-%' AND `post_id`<>{$post_id};" );

		for ( $i = 1; $i < 9999999; $i ++ ) {
			$new_sku = "{$origin}-{$i}";
			if ( ! in_array( $new_sku, $existed ) )
				break ;
		}

		return $new_sku;
	}

	/**
	 * Adds new product variation
	 * @since  0.1
	 * @access public
	 * @param  int      $primary_id    The primary product ID
	 * @return object   $result        An instance of the class WP_Error (if something wrong) or WP_Post (in case of success)
	 */
	public function insert_variation( $primary_id = '' ) {

		$primary_id = absint( $primary_id );

		$primary_post = get_post( $primary_id );

		if ( empty( $primary_id ) )
			return new WP_Error( 'bkng_no_parent_variation', __( 'Cannot get the parent post', BWS_BKNG_TEXT_DOMAIN ) );

		$result = wp_insert_post(
			array(
				'post_parent'    => $primary_id,
				'post_type'      => BWS_BKNG_VARIATION,
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'post_status'    => get_post_status( $primary_id ),
				'post_title'     => "#{$primary_id} variation",
				'post_content'   => "#{$primary_id} variation",
				'post_password'  => ( empty( $primary_post->post_password ) ? '' : $primary_post->post_password )
			)
		);

		if ( empty( $result ) ) {
			return new WP_Error( 'bkng_no_save_result_variation', __( 'Cannot create the variation', BWS_BKNG_TEXT_DOMAIN ) );
		} elseif ( is_wp_error( $result ) ) {
			return $result;
		} else {
			return get_post( $result );
		}
	}

	/**
	 * Update all the product variations (including primary) data
	 * @see    self::save_post()
	 * @since  0.1
	 * @access private
	 * @param  int       $post_id         The primary product ID
	 * @param  string    $action          What kind of action must be executed
	 * @param  string    $value           Th additional value to be used during the action execution
	 * @return void
	 */
	private function update_variations( $post_id, $action = 'set_category', $value = '' ) {
		global $bws_bkng;
		$args = array(
			'post_parent' => $post_id,
			'post_type'   => BWS_BKNG_VARIATION,
		);

		/* get the list of all variations */
		$post_ids = array_merge( $bws_bkng->array_map( 'array_column', get_children( $args ), array( 'ID' ) ));

		if ( empty( $post_ids ) )
			return;

		switch ( $action ) {
			case 'update_category':
				foreach( $post_ids as $id )
					wp_set_post_terms( $id, $value, BWS_BKNG_CATEGORIES );
				break;
			case 'update_status':

				$args = array( 'post_status' => $value );

				if ( 'publish' == $value ) {
					$parent = get_post( $post_id );
					$args['post_password'] = $parent->post_password;
				}

				foreach( $post_ids as $id )
					wp_update_post( array_merge( array( 'ID' => $id ), $args ) );
				break;
			case 'delete':
				foreach( $post_ids as $id )
					wp_delete_post( $id, true );
				break;
			default:
				break;
		}
	}

	/**
	 * Adds 'bkng_variation' parameter to the redirect link after the product saving
	 * @uses   In order to return to the current variation edit page
	 * @see    BWS_BKNG_Admin::__construct()
	 * @since  0.1
	 * @access public
	 * @param  string     $location    Raw redirect link
	 * @param  int        $post_id     The currently managed variation ID
	 * @return string     $location
	 */
	public function add_redirect_param( $location, $post_id ) {

		if ( empty( $_SESSION['redirect_params'] ) )
			return $location;

		$location = add_query_arg( $_SESSION['redirect_params'], $location );

		unset( $_SESSION['redirect_params'] );

		return $location;
	}

	/**
	 * Disaplays service messages
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	*/
	public function display_notices() {
		global $bws_bkng, $post_type_array;
		if ( isset( $_GET[ $this->error_slug ] ) ) {
			switch ( $_GET[ $this->error_slug ] ) {
				case 'add_new':
					$error = __( 'There were some errors during the copying of the product', BWS_BKNG_TEXT_DOMAIN );
					break;
				case 'delete':
					$error = __( 'There were some errors during the removing of the product', BWS_BKNG_TEXT_DOMAIN );
					break;
				case 'copy':
					$error = __( 'There were some errors during the copying of the product', BWS_BKNG_TEXT_DOMAIN );
					break;
				default:
					break;
			}

			if ( ! empty( $error ) )
				echo $bws_bkng->get_errors( $error );
		}

		if ( isset( $_GET[ $this->message_slug ] ) ) {
			switch ( $_GET[ $this->message_slug ] ) {
				case 'add_new':
					$message = $post_type_array[ $this->post_type ]['labels']['name'] . ' ' . __( 'variation has been added successfully', BWS_BKNG_TEXT_DOMAIN );
					break;
				case 'delete':
					$message = $post_type_array[ $this->post_type ]['labels']['name'] . ' ' . __( 'variation has been removed successfully', BWS_BKNG_TEXT_DOMAIN );
					break;
				case 'copy':
					$message = $post_type_array[ $this->post_type ]['labels']['name'] . ' ' . __( 'variation has been copied successfully', BWS_BKNG_TEXT_DOMAIN );
					break;
				default:
					$message = '';
					break;
			}

			if ( ! empty( $message ) )
				echo $bws_bkng->get_messages( $message );
		}
	}

	/**
	 * Register the hook in order to display the metabox on the products edit page
	 * @see    BWS_BKNG_Data_Loader::register_booking_objects()
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public static function add_meta_boxes() {
		global $bws_metaboxes;

		/**
		 * Check whether the current class have been already instantiated.
		 * @uses Due to the fact that the current method is bound to the action-hook "add_meta_box" on "init" and "register_activation_hook" hooks
		 * and there is a possibility that due to the WP plugin activation functionality the class was not instantiated.
		 * @see BWS_BKNG_Data_Loader::register_post_types(),
		 *      BWS_BKNG::__construct(),
		 *      BWS_BKNG::init(),
		 *      BWS_BKNG::flush_rewrite_rules()
		 */
		$instance = BWS_BKNG_Post_Metabox::get_instance();

		if ( isset( $bws_metaboxes[ $instance->post_type ]['args'] ) && in_array( 'preferences', $bws_metaboxes[ $instance->post_type ]['args'] ) ) {
			add_meta_box(
				'bws_booking_' . $instance->post_type . '_attributes',
				__( 'Preferences', BWS_BKNG_TEXT_DOMAIN ),
				array( $instance, 'display_preferences' ),
				$instance->post_type,
				'advanced',
				'default'
			);
		}
		if ( isset( $bws_metaboxes[ $instance->post_type ]['args'] ) && in_array( 'gallery', $bws_metaboxes[ $instance->post_type ]['args'] ) ) {
			add_meta_box(
				'bws_booking_' . $instance->post_type . '_gallery',
				__( 'Gallery', BWS_BKNG_TEXT_DOMAIN ),
				array( $instance, 'display_gallery' ),
				$instance->post_type,
				'advanced',
				'default'
			);
		}
	}

	/**
	 * Displays the metabox "Preferences"
	 * @see    self::add_meta_box()
	 * @since  0.1
	 * @access public
	 * @param  string  $post               The primary product object - an instance of the class WP_Post
	 * @return void
	 */
	public function display_preferences( $post ) {
		global $bws_bkng, $hook_suffix;

		$result = $this->set_posts( $post );

		if ( is_string( $result ) ) {
			echo $bws_bkng->get_errors( $result );
			return;
		}

		if ( $bws_bkng->allow_variations && 'post-new.php' != $hook_suffix && $bws_bkng->get_option( 'enable_variations' ) ) {
			$this->add_variations_tabs();
		}

		$this->add_preferences();
	}

	/**
	 * Displays the metabox "Gallery"
	 * @see    self::add_meta_box()
	 * @since  0.1
	 * @access public
	 * @return void
	 */
	public function display_gallery() {
		$gallery = new BWS_BKNG_Image_Gallery( $this->post->ID, $this->name );
		echo $gallery->get_content();
	}

	/**
	 * Set values of self::$post and self ::$variation_id for further work
	 * @see    self::display_preferences()
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return string|boolean
	 */
	public function set_posts( $post ) {

		$this->post = $post instanceof WP_POST ? $post : get_post( absint( $post ) );

		if ( ! $this->post )
			return __( "Cannot get product's data", BWS_BKNG_TEXT_DOMAIN );

		if ( isset( $_GET[ $this->variation_slug ] ) )
			$variation_id = absint( $_GET[ $this->variation_slug ] );
		elseif ( isset( $_POST[ $this->variation_slug ] ) && defined( 'DOING_AJAX' ) && DOING_AJAX )
			$variation_id = absint( $_POST[ $this->variation_slug ] );

		$this->variation = empty( $variation_id ) || $this->post->ID == $variation_id ? $this->post : get_post( $variation_id );

		if ( ! $this->variation )
			return __( "Cannot get product's data", BWS_BKNG_TEXT_DOMAIN );

		return true;
	}

	/**
	 * Displays the variations tabs list
	 * @see    self::display_preferences()
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function add_variations_tabs() {
		global $wpdb, $bws_bkng;

		$primary_id   = $this->post->ID;
		$child_posts  = $wpdb->get_col( "SELECT `ID` FROM {$wpdb->posts} WHERE `post_parent`={$primary_id} AND `post_type`='" . BWS_BKNG_VARIATION . "'" );
		$posts_ids    = array_merge( array( $primary_id ), $child_posts );
		$primary_link = get_edit_post_link( $primary_id );
		$current_id   = $this->variation instanceof WP_Post ? $this->variation->ID : $primary_id;

		do_action( 'bws_bkng_before_post_tabs_variations', $current_id ); ?>

		<h3 class="nav-tab-wrapper bkng_variations_tabs ">
			<?php $nonce = wp_create_nonce( $this->action_slug );
			foreach( $posts_ids as $post_id ) {
				$is_primary  = $post_id == $primary_id;
				$is_current  = $post_id == $current_id;
				$tab_class   = $is_current ? ' nav-tab-active' : '';
				$edit_link   = $is_primary ? $primary_link : add_query_arg( $this->variation_slug , $post_id, $primary_link );
				$copy_link   = add_query_arg( array ( $this->action_slug => 'copy', $this->nonce_slug => $nonce ), $edit_link );
				$remove_link = add_query_arg( array ( $this->action_slug => 'delete', $this->nonce_slug => $nonce ), $edit_link );
				$sku         = esc_attr( get_post_meta( $post_id, 'bkng_sku', true ) );
				$tab_title   = empty( $sku ) ? "#{$post_id}" : $sku; ?>
				<div class="nav-tab<?php echo esc_attr( $tab_class ); ?>" >
					<a
					data-id="<?php echo esc_attr( $post_id ); ?>"
					data-action=""
					href="<?php echo esc_url( $edit_link ); ?>">
						<?php echo esc_html( $tab_title ); ?>
					</a>
					<ul class="<?php echo $is_primary ? 'bkng_primary_tab_menu' : ''; ?>">
						<li>
							<a
							data-id="<?php echo esc_attr( $post_id ); ?>"
							data-action="copy"
							href="<?php echo esc_url( $copy_link ); ?>"
							class="dashicons dashicons-admin-page" title="<?php _e( 'Copy', BWS_BKNG_TEXT_DOMAIN ); ?>"></a>
						</li>
						<?php /* Can not remove the origin product */
						if ( ! $is_primary ) { ?>
							<li>
								<a
								data-id="<?php echo esc_attr( $post_id ); ?>"
								data-action="delete"
								href="<?php echo esc_url( $remove_link ); ?>"
								class="dashicons dashicons-trash" title="<?php _e( 'Delete', BWS_BKNG_TEXT_DOMAIN ); ?>"></a>
							</li>
						<?php } ?>
					</ul>
				</div>
			<?php }
			$add_new_link = add_query_arg( array ( $this->action_slug => 'add_new', $this->nonce_slug => $nonce ), $primary_link ); ?>
			<div>
				<a
					data-id=""
					data-action="add_new"
					href="<?php echo esc_url( $add_new_link ); ?>"
					class="nav-tab"
					title="<?php _e( 'Add new variation', BWS_BKNG_TEXT_DOMAIN ); ?>">+</a>
				<?php
					echo
					/**
					 * uses to initialize the saving data of currently managed variation via JS before switching to another variation
					 */
					/* Contains the edited variation ID */
					$bws_bkng->get_hidden_input( array( 'name' => 'bkng_edited_variation', 'value' => $current_id ) ) .
					/* Contains the variation ID which is necessary to switch to after the page reloading  */
					$bws_bkng->get_hidden_input( array( 'name' => $this->variation_slug ) ) .
					/* Contains the action slug ( add_ne, copy, delete, etc. ) */
					$bws_bkng->get_hidden_input( array( 'name' => $this->action_slug ) ) .
					/* Contains the nonce field */
					$bws_bkng->get_hidden_input( array( 'name' => $this->nonce_slug, 'value' => $nonce ) ); ?>
			</div>
		</h3>
	<?php }

	/**
	 * Displays the currently managed variation options
	 * @see    self::display_preferences()
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function add_preferences() {
		global $bws_metaboxes;

		/**
		 * The list of tabs
		 * @var array
		 * Format field example:
		 * 'field_slug' => array(
		 *     'label'    => 'tab label'   - tab label {@var string}
		 *     'callback' => 'my_function' - callback function name for the tab content display {@var string|array}
		 * )
		 */

		$default_tabs = array(
			'general' => array(
				'label'    => __( 'General', BWS_BKNG_TEXT_DOMAIN ),
				'callback' => array( $this, 'add_general_tab' )
			)
		);

		if ( isset( $bws_metaboxes[ $this->post_type ]['price_tab'] ) )
			$default_tabs['price'] = array(
				'label'    => __( 'Price', BWS_BKNG_TEXT_DOMAIN ),
				'callback' => array( $this, 'add_price_tab' )
			);

		$default_tabs['attributes'] = array(
			'label'    => __( 'Attributes', BWS_BKNG_TEXT_DOMAIN ),
			'callback' => array( $this, 'add_attributes_tab' )
		);
		$default_tabs['locations'] = array(
			'label'    => __( 'Locations', BWS_BKNG_TEXT_DOMAIN ),
			'callback' => array( $this, 'add_locations_tab' )
        );

		$tabs = apply_filters( 'bws_bkng_meta_tabs', $default_tabs );
		$tabs = empty( $tabs ) || ! is_array( $tabs ) ? $default_tabs : $tabs;

		$wrap =
			'<noscript>
				<div class="error">
					<p>' . __( 'Please enable JavaScript to manage the data.', BWS_BKNG_TEXT_DOMAIN ) . '</p>
				</div>
			</noscript>
			<div id="bkng_tabs_wrap">
				<div id="bkng_tab_links_background"></div>
				<ul id="bkng_preferences_tab_links">%s</ul>
				%s
			</div>
			<div class="clear"></div>';
		$tab_link = $tab_content = '';
		foreach( $tabs as $tab => $data ) {
			try {
				$label        = $data['label'];
				/**
				 * Filter to change the default tab content
				 * @param    string   An HTML-structure of the tab content
				 */
				$content      = apply_filters( 'bws_bkng_' . $tab . '_tab_content', call_user_func( $data['callback'] ) );
				$tab_link    .=
					'<li>
						<a id="bkng_tab_link_' . esc_attr( $tab ) . '" href="' . esc_url( "#bkng_tab_{$tab}") . '" title="' . esc_attr( $label ) . '">
							<span class="bkng_tab_label">' . esc_html( $label ) . '</span>
						</a>
					</li>';
				$tab_content .=
					'<div id="bkng_tab_' . esc_attr( $tab ) . '" class="bws_tab bkng_preferences_tab">
						<div class="bkng_tab_content">' . $content . '</div>
					</div>';
			} catch( Exception $e ) {
				//
			}
		}
		echo sprintf( $wrap, $tab_link, $tab_content );
	}

	/**
	 * Displays the content of "General" options tab
	 * @see    self::add_preferences()
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return string      The tab content
	 */
	public function add_general_tab() {
		global $bws_bkng, $wpdb, $bws_metaboxes;

		$rows = array();

		$general_attributes = $wpdb->get_results( 'SELECT * FROM `' . BWS_BKNG_DB_PREFIX . $this->post_type . '_field_ids` WHERE `visible_status` = 0 ORDER BY `field_id` ASC', ARRAY_A );

		if( ! empty( $general_attributes ) ) {
			foreach ( $general_attributes as $attribute ) {
				if ( ! in_array( str_replace( $bws_bkng->plugin_prefix . '_', '', $attribute['field_slug'] ), $bws_metaboxes[ $this->post_type ]['general_tab'] ) ) {
					continue;
				}
				switch ( $attribute['field_slug'] ) {
					case $bws_bkng->plugin_prefix . '_' . 'email':
						$rows[] = $this->display_field( $attribute, 'email' );
						break;
					case $bws_bkng->plugin_prefix . '_' . 'web':
					case $bws_bkng->plugin_prefix . '_' . 'sku':
						$rows[] = $this->display_field( $attribute, 'text' );
						break;
					case $bws_bkng->plugin_prefix . '_' . 'phone':
						$rows[] = $this->display_field( $attribute, 'tel' );
						break;
					case $bws_bkng->plugin_prefix . '_' . 'statuses':
						$rows[] = $this->display_product_status( $attribute );
						break;
					// case $bws_bkng->plugin_prefix . '_' . 'price_on_request':
					case $bws_bkng->plugin_prefix . '_' . 'quantity_available':
						$rows[] = $this->display_field( $attribute, 'checkbox' );
						break;
					case $bws_bkng->plugin_prefix . '_' . 'price':
						$rows[] = $this->display_field( $attribute, 'number', 'price' );
						break;
					case $bws_bkng->plugin_prefix . '_' . 'rental_interval':
						$rows[] = $this->display_rental_interval( $attribute );
						break;
					case $bws_bkng->plugin_prefix . '_' . 'quantity':
					case $bws_bkng->plugin_prefix . '_' . 'minimum_stay':
						$rows[] = $this->display_field( $attribute, 'number' );
						break;
					case $bws_bkng->plugin_prefix . '_' . 'check_in':
					case $bws_bkng->plugin_prefix . '_' . 'check_out':
						$rows[] = $this->display_field( $attribute, 'time' );
						break;
					case $bws_bkng->plugin_prefix . '_' . 'category':
						$rows[] = $this->display_category( $attribute );
						break;
					case $bws_bkng->plugin_prefix . '_' . 'agencies':
						if( $this->is_main_product() ) {
							$rows[] = $this->display_agencies( $attribute );
						}
						break;
				}
			}
		}

		return implode( '', $rows );
	}

	private function display_field( $attribute, $type, $field = '' ){
		global $wpdb, $bws_bkng;
		$id       = $attribute['field_slug'] . '_' . $attribute['field_id'];
		$name     = $bws_bkng->plugin_prefix . '_attribute[' . $attribute['field_id'] . ']';
		$db_value	= $wpdb->get_var( $wpdb->prepare( 'SELECT `post_value` FROM `' . BWS_BKNG_DB_PREFIX . $this->post_type . '_field_post_data` WHERE `field_id` = %d AND `post_id` = %d', $attribute['field_id'], get_the_ID() ) );
		$value    = empty( $db_value ) ? "" : $db_value;
		switch( $type ) {
			case 'email':
			case 'tel':
				$callback = array( $bws_bkng, 'get_input' );
				$args     = array( compact( 'name', 'value', 'id', 'type' ) );
				break;
			case 'time':
				$callback = array( $bws_bkng, 'get_input' );
				$attr			= ''; /* 'min="08:00" max="22:00" step=""'; */
				$args     = array( compact( 'name', 'value', 'id', 'type', 'attr') );
				break;
			case 'text':
				$callback = array( $bws_bkng, 'get_text_input' );
				if( 'sku' == $field ){
					$value    = empty( $db_value ) ? '#' . $this->variation->ID : $db_value;
				}
				$args     = array( compact( 'name', 'value', 'id' ) );
				break;
			case 'select':
				$callback = array( $bws_bkng, 'get_select' );
				$args       = array( compact( 'name', 'options', 'selected', 'id' ) );
				break;
			case 'checkbox':
				$callback = array( $bws_bkng, 'get_checkbox' );
				$attr     = $db_value ? 'checked="checked"' : '';
				$args     = array( compact( 'name', 'attr', 'id' ) );
				break;
			case 'number':
				$callback = array( $bws_bkng, 'get_number_input' );
				$value    = empty( $db_value ) ? 0 : esc_attr( $db_value );
				if ( 'price' == $field ) {
					$step     = 1 / pow( 10, absint( $bws_bkng->get_option( 'number_decimals' ) ) );
					$args			= array( compact( 'name', 'value', 'step', 'id' ) );
				} elseif ( 'stars' == $field ) {
					$value      = absint( $value );
					$min				= '1';
					$max				= '5';
					$value			= empty( $db_value ) ? 1 : absint( $db_value );
					$args       = array( compact( 'name', 'value', 'id', 'min', 'max' ) );
				} elseif ( 'price_by_days' == $field ) {

				} else {
					$value      = absint( $value );
					$args       = array( compact( 'name', 'value', 'id' ) );
				}
				break;

		}
		$wrap_class = $attribute['field_slug'] . '_row';
		return $this->get_row( $callback, $attribute['field_name'], $args, '', $wrap_class );
	}

	private function display_product_status( $attribute ){
		global $wpdb, $bws_bkng;
		/**
		 * Products status
		 */
		$callback = array( $bws_bkng, 'get_select' );
		$statuses = $bws_bkng->get_post_type_option( $this->post_type, 'products_statuses' );
		if( ! empty( $statuses ) ) {
			$name     = $bws_bkng->plugin_prefix . '_attribute[' . $attribute['field_id'] . ']';
			$id       = $attribute['field_slug'] . '_' . $attribute['field_id'];
			$selected = $wpdb->get_var( $wpdb->prepare( 'SELECT `post_value` FROM `' . BWS_BKNG_DB_PREFIX . $this->post_type . '_field_post_data` WHERE `field_id` = %d AND `post_id` = %d', $attribute['field_id'], get_the_ID() ) );
			$options  = array();
			foreach( $statuses as $slug => $data ) {
				$options[ $slug ] = $data['title'];
			}
			$args       = array( compact( 'name', 'options', 'selected', 'id' ) );
			$wrap_class = $attribute['field_slug'] . '_row';
			return $this->get_row( $callback, $attribute['field_name'], $args, '', $wrap_class );
		}
		return '';
	}

	private function display_rental_interval( $attribute ) {
		global $bws_bkng;
		/**
		 * Rent interval
		 */
		$callback  = array( $bws_bkng, 'get_select' );
		$intervals = $bws_bkng->get_rent_interval();
		$name      = $bws_bkng->plugin_prefix . '_attribute[' . $attribute['field_id'] . ']';
		$id       =  $attribute['field_slug'] . '_' . $attribute['field_id'];
		$selected  = $bws_bkng->get_product_rent_interval( $this->variation->ID );
		$options   = array();
		foreach ( $intervals as $slug => $data ) {
			$options[ $slug ] = $data[0];
		}
		$args       = array( compact( 'name', 'options', 'selected', 'id' ) );
		$wrap_class = $attribute['field_slug'] . '_row';
		return $this->get_row( $callback, $attribute['field_name'], $args, '', $wrap_class );
	}

	private function display_category( $attribute ){
		global $bws_bkng;
		/**
		 * Category
		 */
		$callback          = 'display_terms_list';
		$taxonomy          = BWS_BKNG_CATEGORIES;
		$display_type      = 'radio';
		$display_new_block = true;
		$args              = compact( 'taxonomy', 'display_type', 'display_new_block' );

		$name   = "bkng_get_attributes_nonce";
		$value  = wp_create_nonce( $name );
		$wrap_class = $attribute['field_slug'] . '_row';
		return $this->get_row( $callback, __( 'Category', BWS_BKNG_TEXT_DOMAIN ), $args, 'bkng_collapsed', $wrap_class ) . $bws_bkng->get_hidden_input( compact( 'name', 'value' ) );
	}

	private function display_agencies( $attribute ){
		global $bws_bkng;
		/**
		 * Add Rental Points lists
		 */
		$callback          = 'display_terms_list';
		$tax_name          = BWS_BKNG_AGENCIES;
		$taxonomy          = $tax_name;
		$display_type      = 'radio';
		$display_new_block = false;
		$args              = compact( 'taxonomy', 'display_type', 'display_new_block' );
		$taxonmy = get_taxonomy( $tax_name );
		$wrap_class = $attribute['field_slug'] . '_row';
		return $this->get_row( $callback, $taxonmy->labels->singular_name, $args, 'bkng_collapsed', $wrap_class );
	}

	public function add_price_tab() {
		global $bws_bkng, $wpdb, $bws_metaboxes, $wp_version;

		$prefix = $bws_bkng->plugin_prefix . '_';
		$fields = "'" . $prefix . implode( "', '" . $prefix, $bws_metaboxes[ $this->post_type ]['price_tab'] ) . "'";

		$fields_data = $wpdb->get_results(
			"SELECT *
			FROM `" . BWS_BKNG_DB_PREFIX . $this->post_type . "_field_ids`
			WHERE `visible_status` = 0
				AND `field_slug` IN ( " . $fields . " )
			ORDER BY `field_id` ASC",
			ARRAY_A
		);

		$values = array();
		foreach ( $fields_data as $field ) {
			$db_value = $wpdb->get_var( $wpdb->prepare( 'SELECT `post_value` FROM `' . BWS_BKNG_DB_PREFIX . $this->post_type . '_field_post_data` WHERE `field_id` = %d AND `post_id` = %d', $field['field_id'], get_the_ID() ) );
			$values[ $field['field_slug'] ] = empty( $db_value ) ? "" : $db_value;
			$ids[ $field['field_slug'] ] = $field['field_id'];
		}
		/**
		 * All variations of price
		 */
		$rows = array();

		$price_type = $bws_bkng->get_option( 'price_type' );

		/**
		 * Whether to show 'Price on request' label instead of product price in the front-end
		 */
		$callback = array( $bws_bkng, 'get_checkbox' );
		$name     = $this->name . '[' . $ids[ $prefix . 'price_on_request' ] . ']';
		$attr     = isset( $values[ $prefix . 'price_on_request' ] ) && !! $values[ $prefix . 'price_on_request' ] ? 'checked="checked"' : '';
		$args     = array( compact( 'name', 'attr' ) );
		$rows[2]   = $this->get_row( $callback, __( 'Price on Request', BWS_BKNG_TEXT_DOMAIN ), $args );

		if ( isset( $price_type ) ) {
		    $price_type = $bws_bkng->is_pro ? $price_type : 'basic';
			switch ( $price_type ) {
				case 'basic':
					/**
					 * Values
					 */
					$on_price_by_days = isset( $values[ $prefix . 'on_price_by_days' ] ) ? $values[ $prefix . 'on_price_by_days' ] : false;

					$callback = array( $bws_bkng, 'get_number_input' );
					$name     = $this->name . '[' . $ids[ $prefix . 'price' ] . ']';
					$value    = isset( $values[ $prefix . 'price' ] ) ? esc_attr( $values[ $prefix . 'price' ] ) : 0;
					$class    = 'bkng_main_price';
					$step     = 1 / pow( 10, absint( $bws_bkng->get_option( 'number_decimals' ) ) );
					$after    = '';
					$before  = '';
					$args    = array( compact( 'name', 'value', 'step', 'after', 'before', 'class' ) );
					$rows[4] = $this->get_row( $callback, __( 'Price', BWS_BKNG_TEXT_DOMAIN ) . "&nbsp;({$bws_bkng->get_option('currency')})", $args );
					if ( $bws_bkng->is_pro ) {
						/**
						* Checkbox for base price
						*/
						$callback = array( $bws_bkng, 'get_checkbox' );
						$name     = $this->name . '[' . $ids[ $prefix . 'on_price_by_days' ] . ']';
						$class 	  = 'bws_option_affect';
						$attr     = !! $on_price_by_days ? 'checked="checked"' : '';
						$attr     .= 'data-affect-show=".bkng_hide_price_by_days"';
						$args     = array( compact( 'name', 'attr', 'class' ) );
						$rows[100]   = $this->get_row( $callback, __( 'Price by days', BWS_BKNG_TEXT_DOMAIN ), $args );

                        /**
                         * Price by days block
                         */
                        $days_input_index = 22;

                        $price_meta_by_days = $values[ $prefix . 'price_by_days' ] ? maybe_unserialize( $values[ $prefix . 'price_by_days' ] ) : false;
                        $default_price = isset( $values[ $prefix . 'price' ] ) ? $values[ $prefix . 'price' ] : 100;

                        /* set default data if not exists bkng_price_by_days */
                        if ( ! $price_meta_by_days ) {
                            $price_meta_by_days = array( 20 => array( 'day_from' => 1, 'day_to' => 2,  'price' => $default_price ) );
                        }

                        /* create inputs rows */
                        foreach ( $price_meta_by_days as $input_row_index => $item_values ) {
                            $rows[ $days_input_index ] = '<div class="bkng_main_wrap_price bkng_hide_price_by_days bkng_main_wrap_price_' . $input_row_index . '">';
                            $inputs_list = '';
                            foreach ( $item_values as $key => $value ) {

                                $labels = array( 'day_from' => __( 'Day From', BWS_BKNG_TEXT_DOMAIN ), 'day_to' => __( 'Day To', BWS_BKNG_TEXT_DOMAIN ), 'price' => __( 'Price', BWS_BKNG_TEXT_DOMAIN ) );

                                $callback   = array( $bws_bkng, 'get_number_input' );
                                $name       = $this->name . '[' . $ids[ $prefix . 'price_by_days' ] . '][' . $input_row_index . '][' . $key . ']';
                                $value      = $item_values[ $key ];
                                $step       = ( 'price' == $key ) ? 1 / pow( 10, absint( $bws_bkng->get_option( 'number_decimals' ) ) ) : 1;
                                $class      = 'bkng_price_by_days ' . 'bkng_' . $key;
                                $row_class  = 'bkng_row_price_by_days';
                                $wrap_class = 'bkng_wrap_price_by_days';
                                $min = ( 'price' == $key ) ? 0 : 1;
                                $after_left    = '';
                                $before_left  = '';
                                /* arg */
                                $args = array( compact( 'name', 'value', 'step', 'after_left', 'before_left', 'class', 'min' ) );

                                $input_label = $labels[ $key ] . ':';
                                /* rows */
                                $inputs_list .= $this->get_row( $callback, __( $input_label, BWS_BKNG_TEXT_DOMAIN ), $args, $row_class, $wrap_class );
                            }
                            $rows[ $days_input_index ] .= $inputs_list;
                            $rows[ $days_input_index ] .= '<div class="bkng_row_cross"></div></div>';
                            $days_input_index += 2;
                        }

                        $rows['100500'] = '<button id="bkng_add_interval" class="button bkng_hide_price_by_days">' . __( 'Add New Interval', BWS_BKNG_TEXT_DOMAIN ) . '</button>';
                    } else {
					    $bws_settings_tabs = new Bws_Settings_Tabs( array( 'plugins_info' => $bws_bkng->get_plugin_info() ) );
						$rows[100] = '<div class="bws_pro_version_bloc"><div class="bws_pro_version_table_bloc"><button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="';
						$rows[100] .=  __( 'Close', 'captcha-bws' );
						$rows[100] .= '"></button><div class="bws_table_bg"></div><table class="form-table bws_pro_version"><tr><td>';
						$callback = array( $bws_bkng, 'get_checkbox' );
						$class 	  = 'bws_option_affect';
						$attr     = !! $on_price_by_days ? '' : '';
						$attr     .= 'data-affect-show=".bkng_hide_price_by_days" disabled';
						$args     = array( compact( 'name', 'attr', 'class' ) );
						$rows[100]   .= $this->get_row( $callback, __( 'Price by days', BWS_BKNG_TEXT_DOMAIN ), $args );
						$rows[100] .= '</td></tr></table></div>';
						ob_start();
						$bws_settings_tabs->bws_pro_block_links();
						$pro_button = ob_get_contents();
						ob_end_clean();
						$rows[100] .= $pro_button . '</div>';
					}
                    break;
				case 'seasons':
					$season_input_index = 12;
					$meta     = isset( $values[ $prefix . 'price_by_seasons' ] ) ? maybe_unserialize( $values[ $prefix . 'price_by_seasons' ] ) : array();;
					$seasons = array(
						'winter' => __( 'Winter Season', BWS_BKNG_TEXT_DOMAIN ),
						'spring' => __( 'Spring Season', BWS_BKNG_TEXT_DOMAIN ),
						'summer' => __( 'Summer Season', BWS_BKNG_TEXT_DOMAIN ),
						'autumn' => __( 'Autumn Season', BWS_BKNG_TEXT_DOMAIN ),
					);
					foreach ( $seasons as $key => $season_name ) {
						$callback = array( $bws_bkng, 'get_number_input' );
						$name     = $this->name . '[' . $ids[ $prefix . 'price_by_seasons' ] . '][' . $key . ']';
						$value    = empty( $meta[$key] ) ? 0 : esc_attr( $meta[$key] );
						$step     = 1 / pow( 10, absint( $bws_bkng->get_option( 'number_decimals' ) ) );
						$args    = array( compact( 'name', 'value', 'step', 'after', 'before' ) );
						$rows[ $season_input_index ] = $this->get_row( $callback, __( 'Price', BWS_BKNG_TEXT_DOMAIN ) . "&nbsp;in " . $season_name, $args );
						$season_input_index += 2;
					}
			}
		}
		return $this->split_in_two_columns( $rows );
    }

	/**
	 * Displays the content of "Extras" options tab
	 * @see    self::add_preferences()
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return string      The tab content
	 */
	public function add_extras_tab() {
		global $bws_bkng;
		$rows  = array();
		$trees = array(
			'extra'    => array( __( 'Extras', BWS_BKNG_TEXT_DOMAIN ), __( 'Select products that will be offered as extras for the currently edited one.', BWS_BKNG_TEXT_DOMAIN ) ),
			/* 'up_sells' => array( __( 'Up-sells', BWS_BKNG_TEXT_DOMAIN ), __( 'Select products which you recommend instead of the currently edited' ) ) */
		);

		$trees = array_filter( array_merge( $trees, (array)apply_filters( 'bws_bkng_trees', array() ) ) );

		$wrap_class = 1 == count( $trees ) ? ' bkng_single_tree' : '';

		foreach ( $trees as $key => $data )
			$rows[] = $this->get_row( array(), $data[0], '', "bkng_tree bkng_{$key}_tree", $wrap_class, $data[1] . '.' );

		return $this->split_in_two_columns( $rows );
	}

	/**
	 * Displays the content of "Attributes" options tab according to the produxt category
	 * @see    self::display_preferences()
	 * @since  0.1
	 * @access public
	 * @param  string|int  $category_id  The product category ID or slug { @uses during the AJAX call }
	 * @return string                    The tab content
	 */
	public function add_attributes_tab( $category_id = '' ) {
		global $bws_bkng, $wpdb, $bws_taxonomies;
		$rows = array();

		$query = 'SELECT * FROM ' . BWS_BKNG_DB_PREFIX . $this->post_type . '_field_ids WHERE `visible_status` = 1';
		$fields_query_result	= $wpdb->get_results( $query, ARRAY_A );
		$query = 'SELECT * FROM ' . BWS_BKNG_DB_PREFIX . $this->post_type . '_field_values';
		$field_values_query_result	= $wpdb->get_results( $query, ARRAY_A );
		foreach( $fields_query_result as $key => $field ) {
			foreach( $field_values_query_result as $key_value => $value ) {
				if( $field['field_id'] == $value['field_id'] ) {
					$fields_query_result[ $key ]['value'][ $value['order'] ] = array( 'value_id' => $value['value_id'], 'value_name' => $value['value_name'] );
					unset( $field_values_query_result[ $key_value ] );
				}
			}
		}
		unset( $field_values_query_result );

		$post_id = get_the_ID();

		foreach ( $fields_query_result as $field ) {
			$post_value = $wpdb->get_var( $wpdb->prepare( 'SELECT `post_value` FROM `' . BWS_BKNG_DB_PREFIX . $this->post_type . '_field_post_data` WHERE `field_id` = %d AND `post_id` = %d', $field['field_id'], $post_id ) );
			switch( $field['field_type_id'] ) {
				case '1': /* input text */
					$callback = array( $bws_bkng, 'get_text_input' );
					$name     = $bws_bkng->plugin_prefix . '_attribute[' . $field['field_id'] . ']';
					$value		= $post_value;
					$args     = array( compact( 'name', 'value' ) );
					$rows[]   = $this->get_row( $callback, $field['field_name'], $args );
					break;
				case '2': /* textarea */
					$callback = array( $bws_bkng, 'get_form_unit' );
					$name     = $bws_bkng->plugin_prefix . '_attribute[' . $field['field_id'] . ']';
					$value		= $post_value;
					$args     = array( compact( 'name', 'value' ) );
					$rows[]   = $this->get_row( $callback, $field['field_name'], $args );
					break;
				case '3': /* checkbox */
					$callback		= array( $bws_bkng, 'get_checkbox' );
					$name				= $bws_bkng->plugin_prefix . '_attribute[' . $field['field_id'] . ']';
					$checked		= $wpdb->get_results( $wpdb->prepare( 'SELECT `post_value` FROM `' . BWS_BKNG_DB_PREFIX . $this->post_type . '_field_post_data` WHERE `field_id` = %d AND `post_id` = %d', $field['field_id'], $post_id ), ARRAY_A );
					$attr				= '';
					if( count( $checked ) == 1 && count( $field['value'] ) == 1 ) {
						$attr = ' checked="checked"';
					}
					$value			= isset( $field['value'] ) ? $field['value'] : '';
					$args				= array( compact( 'name', 'value', 'checked', 'attr' ) );
					$rows[]			= $this->get_row( $callback, $field['field_name'], $args );
					break;
				case '4': /* radio button */
					$callback = array( $bws_bkng, 'get_checkbox' );
					$name     = $bws_bkng->plugin_prefix . '_attribute[' . $field['field_id'] . ']';
					$attr     = $post_value ? 'checked="checked"' : '';
					$value    = isset( $field['value'] ) ? $field['value'] : '';
					$args     = array( compact( 'name', 'value', 'attr' ) );
					$rows[]   = $this->get_row( $callback, $field['field_name'], $args );
					break;
				case '5': /* select */
					$callback = array( $bws_bkng, 'get_select' );
					$name     = $bws_bkng->plugin_prefix . '_attribute[' . $field['field_id'] . ']';
					$options = array();
					foreach( $field['value'] as $value ) {
						$options[ $value['value_id'] ] = $value['value_name'];
					}
					$selected = $post_value;
					$args     = array( compact( 'name', 'options', 'selected' ) );
					$rows[]   = $this->get_row( $callback, $field['field_name'], $args );
					break;
				case '7': /* time */
					$callback = array( $bws_bkng, 'get_input' );
					$name     = $bws_bkng->plugin_prefix . '_attribute[' . $field['field_id'] . ']';
					$min			= '09:00';
					$max			= '18:00';
					$type			= 'time';
					$args     = array( compact( 'name', 'value', 'min', 'max', 'type' ) );
					$rows[]   = $this->get_row( $callback, $field['field_name'], $args );
					break;
				case '9': /* number */
					$callback = array( $bws_bkng, 'get_number_input' );
					$name     = $bws_bkng->plugin_prefix . '_attribute[' . $field['field_id'] . ']';
					$value		= $post_value;
					$args     = array( compact( 'name', 'value' ) );
					$rows[]   = $this->get_row( $callback, $field['field_name'], $args );
					break;
				default:
					break;
			}
		}
		if ( ! empty( $bws_taxonomies ) ) {
			foreach ( $bws_taxonomies as $post_type => $taxonomies ) {
				if ( $post_type == $this->post_type ) {
					foreach ( $taxonomies as $taxonomy_slug => $taxonomy_args ) {
						if ( isset( $taxonomy_args['meta_box_cb'] ) && $taxonomy_args['meta_box_cb'] === false ) {
							$callback          = 'display_terms_list';
							$taxonomy          = $taxonomy_slug;
							$display_type      = 'select_checkboxes';
							$display_new_block = false;
							$args              = compact( 'taxonomy', 'display_type', 'display_new_block' );
							$rows[]            = $this->get_row( $callback, $taxonomy_args['labels']['name'], $args, 'bkng_collapsed' );
						}
					}
				}
			}
		}

		return implode( '', $rows );
	}

	/**
	 * Displays the content of "Extras" options tab
	 * @see    self::add_preferences()
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return string      The tab content
	 */
	public function add_locations_tab() {
		global $wpdb, $bws_bkng;

		$post_id = get_the_ID();
		$rows  = array();
		$query = 'SELECT * FROM ' . BWS_BKNG_DB_PREFIX . 'locations ORDER BY `location_name`';
		$locations_query_result	= $wpdb->get_results( $query, ARRAY_A );
		$location_posts = $wpdb->get_row( $wpdb->prepare( 'SELECT `id`, `location_id` FROM `' . BWS_BKNG_DB_PREFIX . 'post_location` WHERE `post_id` = %d', $post_id ), ARRAY_A );

        $callback = array( $bws_bkng, 'get_select' );
		$name     = $bws_bkng->plugin_prefix . '_locations';
		$options = array();
		$options[0] = __( 'Select location', BWS_BKNG_TEXT_DOMAIN );
		foreach( $locations_query_result as $location ) {
			$options[ $location['location_id'] ] = $location['location_name'];
		}
		$location_posts['location_id'] = $location_posts['location_id'] ?? '';
		$location_posts['id'] = $location_posts['id'] ?? '';
        $selected = $location_posts['location_id'];
		$args     = array( compact( 'name', 'options', 'selected' ) );
		$rows[]   = $this->get_row( $callback, 'Locations', $args );
        if( '' != $location_posts['id'] ) {
			$callback = array( $bws_bkng, 'get_hidden_input' );
			$name     = $bws_bkng->plugin_prefix . '_locations_id';
			$value    = $location_posts['id'];
			$args     = array( compact( 'name', 'value' ) );
			$rows[]   = $this->get_row( $callback, '', $args );
		}
		return implode( '', $rows );
	}

	/**
	 * Displays the single option block
	 * @see    self::add_attributes_tab()
	 * @since  0.1
	 * @access public
	 * @param  string   $callback         The callback function to display the option content
	 * @param  string   $label            The block title
	 * @param  string   $args             The list of parameters to be transfered to callback
	 * @param  string   $row_class        The block row class
	 * @param  string   $wrap_class       The block class
	 * @param  string   $tooltip          The tooltip
	 * @return string                     The block html-content
	 */
	public function get_row( $callback, $label, $args = array(), $row_class = '', $wrap_class = '', $tooltip = '' ) {
		global $bws_bkng;
		ob_start(); ?>
		<div class="bkng_meta_row <?php echo esc_attr( $wrap_class ); ?>">
			<div class="bkng_meta_label_wrap"><?php echo esc_html( $label ); ?></div>
			<div class="bkng_meta_input_wrap <?php echo esc_attr( $row_class ); ?>">
				<?php if ( $tooltip ) { ?>
					<p><?php echo $bws_bkng->get_info( $tooltip ); ?></p>
				<?php }

				$callback = is_array( $callback ) ? $callback : array( $this, strval( $callback ) );

				if ( ! empty( $callback ) )
					echo call_user_func_array( $callback, $args ); ?>
			</div>
		</div>
		<?php $row = ob_get_contents();
		ob_end_clean();
		return $row;
	}

	/**
	 * Display the list of available items (terms) for list-type attributes (taxonomies)
	 * @since  0.1
	 * @access public
	 * @param  string    $taxonomy           The taxonomy slug
	 * @param  string    $display_type       The attribute type
	 * @param  boolean   $display_new_block  Whether to display the "Add New ..." block
	 * @return void
	 */
	public function display_terms_list( $taxonomy, $display_type = 'radio', $display_new_block = true ) {
		global $wp_version, $bws_bkng, $hook_suffix;

		/* get all terms for the given taxonomy */
		$terms = $bws_bkng->get_terms( $taxonomy, array( 'hide_empty' => false ) );

		if ( is_wp_error( $terms ) ) {
			echo $bws_bkng->get_errors( $terms, '', 'inline' );
			return;
		}

		$is_empty      = empty( $terms );
		$is_checkboxes = "select_checkboxes" == $display_type;
		$default_category_id = absint( get_option( 'default_' . BWS_BKNG_CATEGORIES ) );
		$checked  = $all = $is_checkboxes ? 0 : '';

		/**
		 * Show "The list is empty..." message for the noscript view mode of the page or
		 * if the "Add new ..." block is disabled for this attribute
		 */
		if ( $is_empty ) {
			$link_text = __( 'the manage attribute page', BWS_BKNG_TEXT_DOMAIN );
			$link      = "<a href=\"edit-tags.php?taxonomy={$taxonomy}&amp;post_type={$this->post->post_type}\">{$link_text}</a>";
			$text      = __( 'The list is empty. To edit it, please go to', BWS_BKNG_TEXT_DOMAIN ) . "&nbsp;" . $link ;
			$block     = $display_new_block ? "<noscript>{$text}</noscript>" : "<div id=\"bkng_empty_{$taxonomy}_list\" class=\"bkng_meta_list\">{$text}</div>";
			echo $block;
		} ?>

		<ul id="<?php echo esc_attr( $taxonomy ); ?>_list" class="bkng_meta_list<?php echo $is_empty ? " " . BWS_BKNG::$hidden: ''; ?>">
			<?php if ( ! $is_empty ) {
				/* get all taxonomy terms for the given post */
				$post_terms = wp_get_post_terms( $this->variation->ID, $taxonomy );
				/* get the list of terms slugs which are associated with the the given post */
				$post_terms_slugs = $bws_bkng->array_map( 'array_column', $post_terms, 'slug' );

				if ( $is_checkboxes ) {
					$func    = 'get_checkbox';
					$checked = count( $post_terms_slugs );
					$all     = count( $terms );
					$name    = "{$this->name}[{$taxonomy}][]";
					$terms_slugs    = $bws_bkng->array_map( 'array_column', $terms, 'slug' );
					$selected_terms = array_intersect( $terms_slugs, $post_terms_slugs );
				} else {
					$func = 'get_radiobox';
					$name = "{$this->name}[{$taxonomy}]";
					/* get the name of the checked term */
					if ( ! empty( $post_terms_slugs[0] ) ) {
						$checked = $this->get_checked_term_field( $terms, $post_terms_slugs[0] );
						$selected_terms = array( $post_terms_slugs[0] );
					} elseif ( BWS_BKNG_CATEGORIES == $taxonomy && $this->is_variation() ) {
						/* get parent product's category */
						$parent_terms = wp_get_post_terms( $this->post->ID, $taxonomy );
						if ( ! empty( $parent_terms[0]->slug ) ) {
							$checked = $this->get_checked_term_field( $terms, $parent_terms[0]->slug );
							$selected_terms = array( $parent_terms[0]->slug );
						}
					}
				}

				if ( ! isset( $selected_terms ) ) {
					$checked = $this->get_checked_term_field( $terms, $default_category_id, 'term_id' );
					$default_term   = get_term_by( 'id', absint( $default_category_id ), BWS_BKNG_CATEGORIES );
					$selected_terms = array( $default_term->slug );
				}

				foreach ( $terms as $term ) {
					$id    = esc_attr( "{$this->name}_{$taxonomy}_{$term->slug}" );
					$value = $term->slug;
					$attr  = in_array( $term->slug, $selected_terms ) ? 'checked="checked"' : '';
					$after = $term->name; ?>
					<li><?php echo $bws_bkng->$func( compact( 'id', 'name', 'value', 'attr', 'after' ) ); ?></li>
				<?php }
			} ?>
		</ul>
		<div class="bkng_placeholder">
			<div class="bkng_terms_count">
				<span class="bkng_checked_count"><?php echo esc_attr( $checked ); ?></span>
				<?php if ( $is_checkboxes ) { ?>
					/<span class="bkng_all_count"><?php echo esc_html( $all ); ?></span>
				<?php } ?>
			</div>
		</div>

		<?php
		/**
		 * For some list-type attributes the "Add new ..." block isn't shown
		 * due to the fact that its items have some meta-fields
		 * required to fill in during the creation
		 */
		if ( $display_new_block )
			$this->display_new_taxonomy_block( $taxonomy, $display_type );
	}

	/**
	 * Displays the the "Add new ..." block for list-type attributes
	 * @see    self::display_terms_list()
	 * @since  0.1
	 * @access public
	 * @param  string    $taxonomy        The taxonomy slug
	 * @param  string    $display_type    The attribute type
	 * @return void
	 */
	private function display_new_taxonomy_block( $taxonomy, $display_type ) {
		global $bws_bkng; ?>
		<div id="<?php echo esc_attr( $taxonomy ); ?>-new" class="hide-if-no-js">
			<?php
				$name  = "bkng_term_name_{$taxonomy}";
				$class = "bkng_new_taxonomy_input";
				$text_field = $bws_bkng->get_text_input( compact( 'name', 'class' ) );

				$name  = $taxonomy;
				$value = __( 'Add New', BWS_BKNG_TEXT_DOMAIN );
				$type  = 'button';
				$class = "button bkng_add_meta_submit";
				$add_new_button = $bws_bkng->get_button_input( compact( 'name', 'value', 'type', 'class' ) );

				$name  = "bkng_term_nonce_{$taxonomy}";
				$value = wp_create_nonce( $taxonomy );
				$nonce_field = $bws_bkng->get_hidden_input( compact( 'name', 'value' ) );

				$name  = "bkng_term_display_type_{$taxonomy}";
				$value = "select_checkboxes" == $display_type ? 'checkbox' : 'radio';
				$item_type_field = $bws_bkng->get_hidden_input( compact( 'name', 'value' ) );

				echo "{$text_field}{$add_new_button}{$nonce_field}{$item_type_field}";
			?>
		</div>
		<?php echo $bws_bkng->get_errors( '', "bkng_ajax_response_{$taxonomy}", "inline bkng_ajax_error " . BWS_BKNG::$hidden );
	}

	/**
	 * Displays the content for  single-location attributes
	 * @since  0.1
	 * @access private
	 * @param  string    $slug     The attribute slug
	 * @return void
	 */
	private function display_location( $slug ) {
		global $bws_bkng;

		$inputs = array(
			'address'   => array( '', $bws_bkng->get_option( 'google_map_default_address' ) ),
			'latitude'  => array( __( 'Latitude', BWS_BKNG_TEXT_DOMAIN ), $bws_bkng->get_option( 'google_map_default_lat' ) ),
			'longitude' => array( __( 'Longitude', BWS_BKNG_TEXT_DOMAIN ), $bws_bkng->get_option( 'google_map_default_lng' ) )
		);

		$buttons = array(
			'show_map'            => array( "{$slug}_button", "bkng_show_map_button button dashicons dashicons-location-alt hide-if-no-js", '' ),
			'find_by_address'     => array( '', 'button bkng_find_by_address_button button', __( 'Find by address', BWS_BKNG_TEXT_DOMAIN ) ),
			'find_by_coordinates' => array( '', 'button bkng_find_by_coordinates_button button', __( 'Find by coordinates', BWS_BKNG_TEXT_DOMAIN ) )
		);
		$input = $button = array();
		foreach ( $inputs as $key => $data ) {
			$before = $data[0];
			$name   = "{$this->name}[{$slug}][{$key}]";
			$class  = "bkng_{$key}_input";
			$meta   = get_post_meta( $this->variation->ID, "{$slug}_{$key}", true );
			$value  = empty( $meta ) ? $data[1] : esc_attr( $meta );
			$input[ $key ] = $bws_bkng->get_text_input( compact( 'before', 'class', 'name', 'value' ) );
		}
		foreach ( $buttons as $key => $data ) {
			$unit  = 'button';
			$id    = $data[0];
			$class = $data[1];
			$value = $data[2];
			$name  = "{$this->name}[{$slug}][{$key}]";
			$button[ $key ] = $bws_bkng->get_form_unit( compact( 'unit', 'class', 'id', 'value', 'name' ) );
		} ?>

		<div><?php echo $input['address'] . $button['show_map']; ?></div>
		<div class="bkng_toggle_displaying <?php echo BWS_BKNG::$hidden; ?>"><?php echo $button['find_by_address']; ?></div>
		<?php echo $bws_bkng->get_errors( '', '', "inline bkng_js_errors bkng_find_by_address_error " . BWS_BKNG::$hidden ); ?>
		<div class="bkng_toggle_displaying bkng_map_wrap hide-if-no-js <?php echo BWS_BKNG::$hidden; ?>" id="<?php echo esc_attr( $slug ); ?>_map_wrap"></div>
		<?php echo $bws_bkng->get_errors( '', '', "inline bkng_js_errors bkng_find_by_coors_error " . BWS_BKNG::$hidden ); ?>
		<div class="bkng_toggle_displaying <?php echo BWS_BKNG::$hidden; ?>"><?php echo $button['find_by_coordinates']; ?></div>
		<div class="bkng_latitude bkng_toggle_displaying <?php echo BWS_BKNG::$hidden; ?>"><?php echo $input['latitude']; ?></div>
		<div class="bkng_longitude bkng_toggle_displaying <?php echo BWS_BKNG::$hidden; ?>"><?php echo $input['longitude']; ?></div>
	<?php }

	/**
	 * Split the content of the tab in two columns
	 * @since  0.1
	 * @access private
	 * @param  array
	 * @return string
	 */
	private function split_in_two_columns( $rows ) {
		$even = $odd = '';
		foreach( $rows as $key => $row ) {
			$side   = absint( $key ) % 2 ? 'even' : 'odd';
			$$side .= $rows[ $key ];
		}
		return
			"<div class=\"bkng_column bkng_left_column\">{$odd}</div>
			<div class=\"bkng_column bkng_right_column\">{$even}</div>
			<div class=\"clear\"></div>";
	}

	/**
	 * Filter to get the terms tha bind to the currently edited variation
	 * @see   self::display_terms_list()
	 * @since  0.1
	 * @access private
	 * @param  object   $terms          The instance of class WP_Term
	 * @param  string   $search         The searched value
	 * @param  string   $by             The name of the property which is usede for search
	 * @param  string   $return_field   The name of the property which would be returned
	 *                                  if the searched value was found
	 * @return mixed                    The value of the property which is specified in the $return_field, false otherwise
	 */
	private function get_checked_term_field( $terms, $search, $by = 'slug', $return_field = 'name' ) {
		foreach ( $terms as $term ) {
			if ( $term->$by == $search )
				return $term->$return_field;
		}

		return false;
	}

	/**
	 * Checks whether the edited product has been created just right now
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return boolean
	 */
	private function is_new_product() {
		global $hook_suffix;
		return 'post-new.php' == $hook_suffix || preg_match( "/post-new\.php/", $_SERVER['REQUEST_URI'] );
	}

	/**
	 * Checks whether the edited product is a main product
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return boolean
	 */
	private function is_main_product() {
		return $this->post->ID == $this->variation->ID;
	}

	/**
	 * Checks whether the edited product is a variation
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return boolean
	 */
	private function is_variation() {
		return
			! $this->is_main_product() &&
			isset( $_REQUEST[ $this->variation_slug ] ) &&
			$_REQUEST[ $this->variation_slug ] == absint( $_REQUEST[ $this->variation_slug ] );
	}

	/**
	 * Allows to get static class properties via class instance
	 * @param   string    $name   A static metod name ahich is need to get
	 * @return  mixed             A static property value, null otherwise
	 */
	public function __get( $name ) {
		return isset( self::$$name ) ? self::$$name : null;
	}
}
