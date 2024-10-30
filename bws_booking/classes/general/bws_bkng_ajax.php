<?php
/**
 * @uses     to handle AJAX-requests
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_AJAX' ) )
	return;

class BWS_BKNG_AJAX {

	/**
	 * The class constructor
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function __construct() {
		/*
		 * AJAX requests from the admin dashboard
		 */
		add_action( 'wp_ajax_bkng_add_term', array( $this, 'add_term' ) );
		add_action( 'wp_ajax_bkng_get_attributes', array( $this, 'get_attributes' ) );
		add_action( 'wp_ajax_add-tag', array( $this, 'save_term_data' ), 0 );
		add_action( 'wp_ajax_delete-tag', array( $this, 'update_term_relationships' ), 0 );
		add_action( 'wp_ajax_bkng_get_tree_items', array( $this, 'get_tree_items' ) );
		add_action( 'wp_ajax_bkng_save_tree', array( $this, 'bkng_save_tree' ) );
		add_action( 'wp_ajax_bkng_save_term_interval', array( $this, 'bkng_save_term_interval' ) );
		add_action( 'wp_ajax_bkng_get_new_interval', array( $this, 'get_interval_row' ) );
		add_action( 'wp_ajax_bkng_del_interval_row', array( $this, 'del_interval_row' ) );
		add_action( 'wp_ajax_bkng_handle_profile_ajax', array( $this, 'handle_profile_ajax' ) );

		/**
		 * AJAX requests from the front end
		 */
		add_action( 'wp_ajax_bkng_toggle_wishlist', array( $this, 'toggle_wishlist' ) );
		add_action( 'wp_ajax_bkng_add_to_cart', array( $this, 'add_to_cart' ) );
		add_action( 'wp_ajax_nopriv_bkng_add_to_cart', array( $this, 'add_to_cart' ) );
		add_action( 'wp_ajax_bkng_add_extras_to_cart', array( $this, 'bkng_add_extras_to_cart' ) );
		add_action( 'wp_ajax_nopriv_bkng_add_extras_to_cart', array( $this, 'bkng_add_extras_to_cart' ) );
	
		/**
		 * AJAX requests from both the front end and the admin dashboard
		 */
		add_action( 'wp_ajax_custom-header-crop', array( $this, 'ajax_header_crop' ) );
	}

	/**
	 * Calls to function of given class that handels ajax request
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function handle_profile_ajax() {
		$class_name = sanitize_text_field( stripslashes( $_POST['class'] ) );

		if ( is_string( $class_name ) && class_exists( $class_name ) ) {
			$class = new $class_name();
			$class->ajax_response();
		}

		wp_die();
	}

	/**
	 * Adds new taxonomy term
	 * @uses   On the product edit page in the site admin panel
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function add_term() {
		check_ajax_referer( $_POST['tax'], 'nonce' );
		$term_name = sanitize_title( $_POST['name'] );
		$taxonomy  = sanitize_text_field( $_POST['tax'] );
		$term_slug = sanitize_title( $_POST['name'] );
		if ( empty( $term_slug ) ) {
			echo __( 'Please enter the valid term slug', BWS_BKNG_TEXT_DOMAIN );
		} else {
			$result = wp_insert_term( $term_name, $taxonomy, array( 'slug' => $term_slug ) );
			if ( is_wp_error( $result ) ) {
				echo $result->get_error_message();
			} else {

				if ( $taxonomy == BWS_BKNG_CATEGORIES )
					update_term_meta( absint( $result['term_id'] ), "bkng_exclude_from_search", false );

				$result = get_term( $result['term_id'] );
				if ( is_wp_error( $result ) )
					echo $result->get_error_message();
				elseif ( is_null( $result ) )
					_e( 'There are some errors occurred. Cannot get the attribute data', BWS_BKNG_TEXT_DOMAIN );
				else
					echo json_encode( $result );
			}
		}
		die();
	}

	/**
	 * Fetch the content of the "Attributes" tabs
	 * @uses   On the product edit page in the site admin panel
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function get_attributes() {
		global $bws_bkng;
		check_ajax_referer( 'bkng_get_attributes_nonce', 'bkng_nonce' );
		$post_metabox  = new BWS_BKNG_Post_Metabox();
		$result        = $post_metabox->set_posts( absint( $_POST['bkng_post_id'] ) );
		$attributes    = is_string( $result ) ? $bws_bkng->get_errors( $result ) : $post_metabox->add_attributes_tab( sanitize_text_field( stripslashes( $_POST['bkng_cat_slug'] ) ) );
		$rent_interval = $bws_bkng->get_category_rent_interval( sanitize_text_field( stripslashes( $_POST['bkng_cat_slug'] ) ) );

		echo json_encode( compact( 'attributes', 'rent_interval' ) );
		die();
	}

	/**
	 * Adds hooks to save additional term taxonomy data
	 * @uses   On the term taxonomies edit page
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function save_term_data() {
		global $bws_bkng;

		if (
			empty( $_POST['taxonomy'] ) ||
			empty( $_POST['post_type'] ) ||
			empty( $_POST['bkng_child_class'] ) ||
			! in_array( $_POST['post_type'], $bws_bkng->get_post_types() )
		)
			return;

		$class = sanitize_text_field( stripslashes( $_POST['bkng_child_class'] ) );
        $taxonomy = sanitize_text_field( stripslashes( $_POST['taxonomy'] ) );
		$instance = new $class( $taxonomy );

		add_action( "create_{$taxonomy}", array( $instance, 'save_term_data' ), 10, 3 );

		/* !!! Don't add the die() function in order to not brake the following terms data handling */
	}

	/**
	 * Adds hooks to process terms taxonomy removing
	 * @uses   On the term taxonomies edit page
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function update_term_relationships() {

		if (
			empty( $_POST['taxonomy'] ) ||
			! in_array( $_POST['taxonomy'], array( BWS_BKNG_CATEGORIES, BWS_BKNG_AGENCIES ) )
		)
			return;

		$taxonomy = sanitize_text_field( stripslashes( $_POST['taxonomy'] ) );

		add_action( "delete_{$taxonomy}", array( 'BWS_BKNG_Default_Term', "change_product_term" ), 10, 4 );

		/* !!! Don't add the die() function in order to not brake the following terms data handling */
	}

	/**
	 * Fecth the list of products and categories
	 * @uses   On the product edit page in the site admin panel
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function get_tree_items() {
		check_ajax_referer( 'bkng_ajax_nonce', 'bkng_nonce' );
		$func = empty( $_POST['bkng_category'] ) ? 'get_categories' : 'get_products';
		echo json_encode( BWS_BKNG_Products_Tree::$func() );
		die();
	}

	/**
	 * Updates the extras binding to the edited product
	 * @uses   On the product edit page in the site admin panel
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function bkng_save_tree() {
		check_ajax_referer( 'bkng_ajax_nonce', 'bkng_nonce' );
		echo json_encode( BWS_BKNG_Products_Tree::save_tree() );
		die();
	}

	/**
	 * Saves the rent interval for the new product category
	 * @uses   On the product categories edit page
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function bkng_save_term_interval() {
		global $bws_bkng;
		check_ajax_referer( 'bkng_ajax_nonce', 'bkng_nonce' );
		$term_id  = absint( $_POST['term_id'] );
		$interval = in_array( $_POST['interval'], array_keys( $bws_bkng->get_rent_interval() ) ) ? sanitize_text_field( stripslashes( $_POST['interval'] ) ) : '';
		if ( ! empty( $term_id ) && ! empty( $interval ) )
			update_term_meta( $term_id, 'bkng_rental_interval', $interval );
		die();
	}

	/**
	 * Adds or remove the product to the user wishlist
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function toggle_wishlist() {
		check_ajax_referer( 'bkng_toggle_wishlist', 'bkng_nonce' );

		$user = BWS_BKNG_User::get_instance();
		$post_id = absint( $_POST['post_id'] );
		if ( $user->is_in_wishlist( $post_id ) ) {
			$user->remove_from_user_wishlist( $post_id );
			_e( 'Add to Wishlist', BWS_BKNG_TEXT_DOMAIN );
		} else {
			$user->add_to_user_wishlist( $post_id );
			_e( 'Remove from Wishlist', BWS_BKNG_TEXT_DOMAIN );
		}

		wp_die();
	}

	/**
	 * Adds the product to the cart
	 * @uses   On the catalog pages in the site front-end
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function add_to_cart() {
		global $bws_bkng;

		check_ajax_referer( "bkng_{$_POST['bkng_action']}_{$_POST['bkng_product']}", 'bkng_nonce' );

		$cart = BWS_BKNG_Cart::get_instance();
		$data = array(
			$_POST['bkng_product'] => array(
				'rent_interval' => array(
					'from' => empty( $_POST['bkng_from'] ) ? '' : absint( $_POST['bkng_from'] ),
					'till' => empty( $_POST['bkng_till'] ) ? '' : absint( $_POST['bkng_till'] )
				),
				'quantity' => empty( $_POST['bkng_quantity'] ) ? '' : absint( $_POST['bkng_quantity'] )
			)
		);

		$result = $cart->add( $data );

		if ( is_wp_error( $result ) ) {
			echo json_encode( $result );
		} elseif( is_array( $result ) ) {
			if ( empty( $_POST['is_single'] ) ) {
				$link = '<a class="bws_bkng_page_link bws_bkng_single_page_link" href="' . esc_url( get_permalink( $result[0] ) ) . '">' . __( 'View more', BWS_BKNG_TEXT_DOMAIN ) . '</a>';
			} else {
				$page = get_post( $bws_bkng->get_option( "checkout_page" ) );
				$link = '<a class="bws_bkng_page_link bws_bkng_checkout_page_link" href="' . esc_url( get_permalink( $page ) ) . '">' . get_the_title( $page ) . '</a>';
			}
			echo json_encode( array( 'link' => $link ) );
		}
		die();
	}

	/**
	 * Adds extras to the cart
	 * @uses   On the product single page in the site front-end
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function bkng_add_extras_to_cart() {
		global $bws_bkng;

		check_ajax_referer( "bkng_checkout_product", 'bkng_nonce' );

		if ( empty( $_POST['bkng_extras'] ) || empty( $_POST['bkng_product'] ) ) {
			echo json_encode( new WP_Error( 'add_to_cart_error', __( 'Cannot add extras to the cart', BWS_BKNG_TEXT_DOMAIN ) ) );
			die();
		}
		$data          = array();
		$rent_interval = array(
			'from' => empty( $_POST['bkng_from'] ) ? '' : absint( $_POST['bkng_from'] ),
			'till' => empty( $_POST['bkng_till'] ) ? '' : absint( $_POST['bkng_till'] )
		);
		$main_product  = absint( $_POST['bkng_product'] );

		foreach( $_POST['bkng_extras'] as $raw ) {

			if ( empty( $raw['id'] ) )
				continue;

			$data[ $raw['id'] ] = array(
				'rent_interval' => $rent_interval,
				'linked_to'     => $main_product,
				'quantity'      => empty( $raw['quantity'] ) ? false : absint( $raw['quantity'] )
			);
		}

		$cart = BWS_BKNG_Cart::get_instance();

		if ( ! $cart->is_in_cart( $main_product ) ) {
			$data = array(
				$main_product => array(
					'rent_interval' => $rent_interval,
					'quantity'      => empty( $_POST['bkng_quantity'] ) ? false : absint( $_POST['bkng_quantity'] ),
					'linked_to'     => false
				)
			) + $data;
		}

		$result = $cart->add( $data );

		if ( is_wp_error( $result ) ) {
			echo json_encode( $result );
		} else {
			$page      = get_post( $bws_bkng->get_option( "checkout_page" ) );
			$page_link = add_query_arg( array( 'bws_bkng_product_id' => $main_product ), get_permalink( $page ) );
			$link = '<a class="bws_bkng_page_link bws_bkng_checkout_page_link" href="' . esc_url( $page_link ) . '">' . get_the_title( $page ) . '</a>';
			echo json_encode(
				array(
					'message' => sprintf( _n( 'One product is added to the cart', '%s products are added to the cart', count( $result ), BWS_BKNG_TEXT_DOMAIN ), count( $result ) ),
					'link'    => $link
				)
			);
		}

		die();
	}

	/**
	 * Add interval row
	 */
	public function get_interval_row() {
		global $bws_bkng, $bws_metabox, $wpdb;

		$bws_metabox = new BWS_BKNG_Post_Metabox;

		$rows_one = '';
		$labels = array( 'day_from' => __( 'Day From', BWS_BKNG_TEXT_DOMAIN ), 'day_to' => __( 'Day To', BWS_BKNG_TEXT_DOMAIN ), 'price' => __( 'Price', BWS_BKNG_TEXT_DOMAIN ) );
		$rows_one .= '<div class="bkng_main_wrap_price bkng_main_wrap_price_' . absint( $_POST['new_inputs_index'] ) . '">';
		foreach ( $labels as $label_key => $label_name ) {
			$callback   = array( $bws_bkng, 'get_number_input' );
			$name       = "bkng_post_meta[" . absint( $_POST['field_id'] ) . "][" . absint( $_POST['new_inputs_index'] ) . "][" . sanitize_text_field( $label_key ) . "]";
			$value      =  ( 'price' == $label_key ) ? 100 : 1;
			$step       = ( 'price' == $label_key ) ? 1 / pow( 10, absint( $bws_bkng->get_option( 'number_decimals' ) ) ) : 1;
			$class      = 'bkng_price_by_days';
			$row_class  = 'bkng_row_price_by_days';
			$wrap_class = 'bkng_wrap_price_by_days';

			/* arg */
			$args = array( compact( 'name', 'value', 'step', 'class' ) );

			$input_label = $label_name . ':';
			/* rows */
			$row_one = $bws_metabox->get_row( $callback, __( $input_label, BWS_BKNG_TEXT_DOMAIN ), $args, $row_class, $wrap_class );

			$rows_one .= $row_one;
		}

		$rows_one .= '<div class="bkng_row_cross"></div></div>';

		$url     = wp_get_referer();
		preg_match( '#[?&](post)=(\d+)#', $url, $values );

		/* format exist data input_id => array( 1-2 => price )  update post_meta bkng_price_per_days */

		$post_type = get_post_type( $values[2] );
		$db_value = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT `post_value`
				FROM `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_post_data`
				WHERE `field_id` = %d
					AND `post_id` = %d',
				absint( $_POST['field_id'] ),
				$values[2]
			)
		);

		$db_value = empty( $db_value ) ? array() : maybe_unserialize( $db_value );

		$wpdb->delete( BWS_BKNG_DB_PREFIX . $post_type . '_field_post_data', array( 'post_id' => $values[2], 'field_id' => absint( $_POST['field_id'] ) ) );
		$db_value[ absint( $_POST['new_inputs_index'] ) ] = array( 'day_from' => '1', 'day_to' => '1', 'price' => '100' );

		$wpdb->insert( 
			BWS_BKNG_DB_PREFIX . $post_type . '_field_post_data', 
			array( 
				'field_id'		=> absint( $_POST['field_id'] ),
				'post_id'		=> $values[2],
				'post_value'	=> maybe_serialize( $db_value ) 
			),
			array( 
				'%d', 
				'%d',
				'%s'
			)
		);

		/* return need input fields */
		echo json_encode( $rows_one );
		die();
	}

	/**
	 * Delete interval row
	 */
	public function del_interval_row() {
		$url     = wp_get_referer();
		preg_match( '#[?&](post)=(\d+)#', $url, $values );

		$post_data = new BWS_BKNG_Post_Data( (int)$values[2] );

		$price_per_days_meta = $post_data->get_attribute( 'price_by_days' );
		$price_per_days_meta = maybe_unserialize( $price_per_days_meta );

		unset( $price_per_days_meta[ $_POST['del_input_index'] ] );

		$post_data->update_attribute( 'price_by_days', $price_per_days_meta );
		
		/* return need input fields */
		echo json_encode( absint( $_POST['del_input_index'] ) );
		die();
	}
	
	/**
	 * Overwrites wordpress' native functionality because it depends on theme support of irrelevant variables
	 * Gets attachment uploaded by Media Manager, crops it, then saves it as a
     * new object. Returns JSON-encoded object details.
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function ajax_header_crop() {

        $posted_data = wp_unslash( $_POST );

        $post_id = isset( $posted_data['id'] ) ? absint( $posted_data['id'] ) : 0;

        check_ajax_referer( 'image_editor-' . $post_id, 'nonce' );

        $crop_details = isset( $posted_data['cropDetails'] ) ? $posted_data['cropDetails'] : '';

        $dimensions = $this->get_header_dimensions( array(
            'height' => absint( $crop_details['height'] ),
            'width'  => absint( $crop_details['width'] ),
        ) );

        $attachment_id = absint( $post_id );

        $cropped = wp_crop_image(
            $attachment_id,
            absint( $crop_details['x1'] ),
            absint( $crop_details['y1'] ),
            absint( $crop_details['width'] ),
            absint( $crop_details['height'] ),
            absint( $dimensions['dst_width'] ),
            absint( $dimensions['dst_height'] )
        );

        if ( ! $cropped || is_wp_error( $cropped ) ) {
            wp_send_json_error( array( 'message' => __( 'Image could not be processed. Please go back and try again.', 'dokan-lite' ) ) );
        }

        /** This filter is documented in wp-admin/custom-header.php */
        $cropped = apply_filters( 'wp_create_file_in_uploads', $cropped, $attachment_id ); // For replication

        $object = $this->create_attachment_object( $cropped, $attachment_id );

        unset( $object['ID'] );

        $new_attachment_id = $this->insert_attachment( $object, $cropped );

        $object['attachment_id'] = $new_attachment_id;
        $object['url']           = wp_get_attachment_url( $new_attachment_id );
        $object['width']         = $dimensions['dst_width'];
        $object['height']        = $dimensions['dst_height'];

        wp_send_json_success( $object );
    }

	final public function get_header_dimensions( $dimensions ) {
        $max_width        = 0;
        $width            = absint( $dimensions['width'] );
        $height           = absint( $dimensions['height'] );
        $theme_width      = 625;
        $theme_height     = 300;
        $has_flex_width   = true;
        $has_flex_height  = true;
        $has_max_width    = false;
        $dst              = array( 'dst_height' => null, 'dst_width' => null );

        // For flex, limit size of image displayed to 625px unless theme says otherwise
        if ( $has_flex_width ) {
            $max_width = 625;
        }

        if ( $has_max_width ) {
            $max_width = max( $max_width, get_theme_support( 'custom-header', 'max-width' ) );
        }
        $max_width = max( $max_width, $theme_width );

        if ( $has_flex_height && ( ! $has_flex_width || $width > $max_width ) ) {
            $dst['dst_height'] = absint( $height * ( $max_width / $width ) );
        } elseif ( $has_flex_height && $has_flex_width ) {
            $dst['dst_height'] = $height;
        } else {
            $dst['dst_height'] = $theme_height;
        }

        if ( $has_flex_width && ( ! $has_flex_height || $width > $max_width ) ) {
            $dst['dst_width'] = absint( $width * ( $max_width / $width ) );
        } elseif ( $has_flex_width && $has_flex_height ) {
            $dst['dst_width'] = $width;
        } else {
            $dst['dst_width'] = $theme_width;
        }

        return $dst;
	}
	
	final public function create_attachment_object( $cropped, $parent_attachment_id ) {
        $parent     = get_post( $parent_attachment_id );
        $parent_url = wp_get_attachment_url( $parent->ID );
        $url        = str_replace( basename( $parent_url ), basename( $cropped ), $parent_url );

        $size       = @getimagesize( $cropped );
        $image_type = ( $size ) ? $size['mime'] : 'image/jpeg';

        $object = array(
            'ID'             => $parent_attachment_id,
            'post_title'     => basename( $cropped ),
            'post_mime_type' => $image_type,
            'guid'           => $url,
            'context'        => 'custom-header',
        );

        return $object;
	}
	
	final public function insert_attachment( $object, $cropped ) {
        $attachment_id = wp_insert_attachment( $object, $cropped );
        $metadata      = wp_generate_attachment_metadata( $attachment_id, $cropped );
        $metadata      = apply_filters( 'wp_header_image_attachment_metadata', $metadata );

        wp_update_attachment_metadata( $attachment_id, $metadata );

        return $attachment_id;
    }
}