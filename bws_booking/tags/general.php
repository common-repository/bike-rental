<?php
/**
 * Contains the list of functions are used in the plugin (in the site front-end, mostly)
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

/**
 * Checks whether the current page is the plugin page
 * @since    0.1
 * @param    void
 * @return   string|false     The page type alias, false otherwise
 */
if ( ! function_exists( 'bws_bkng_get_booking_page' ) ) {
	function bws_bkng_get_booking_page() {
		global $bws_bkng;
		$page = $bws_bkng->is_booking_page();

		if ( $page )
			return $page;

		return is_singular( $bws_bkng->get_post_types() ) ? 'single_product' : false;
	}
}

/**
 * Includes the plugin HTML template
 * @since  0.1
 * @param  string   $slug    The template slug.
 * @param  string   $name    The template folder.
 * @return void
	 */
if ( ! function_exists( 'bws_bkng_get_template_part' ) ) {
	function bws_bkng_get_template_part( $slug, $name = '' ) {

		$template_name = empty( $name ) ? "{$slug}.php" : "{$slug}-{$name}.php";

		/**
		 * Search the template in "bws-templates" theme folder
		 */

		/**
		 * The filter may be used in order the re-locate the templates folder from another one
		 * (3rd party plugin folder, etc)
		 */
		$templates = apply_filters( 'bws_bkng_get_template_part', "bws-templates/{$template_name}" );
		$templates = (array)$templates;
		$template  = locate_template( $templates );

		/**
		 * If template wasn't found try to inclucde template from the Booking core
		 */
		if ( ! $template )
			$template = BWS_BKNG_PATH . "templates/{$template_name}";

		if ( file_exists( $template ) ) {

			do_action( "bws_bkng_before_template", $template_name );

			load_template( $template, false );

			do_action( "bws_bkng_after_template", $template_name );

		} else {
			do_action( "bws_bkng_no_template", $template_name );
		}
	}
}

/**
 * Fetch the list of query parameters fo getting products
 * @since    0.1
 * @param    void
 * @return   array
 */
if ( ! function_exists( 'bws_bkng_get_query' ) ) {
	function bws_bkng_get_query() {
		if ( is_admin() ) {
			return false;
		}

		$query_parser = BWS_BKNG_Query_Parser::get_instance();
		return $query_parser->get_query_args();
	}
}

/**
 * Fetch the specified page type link
 * @since    0.1
 * @param    string  $slug   The page type slug
 * @param    array   $extra  The list of additional link parameters
 * @return   string          The page type permalink
 */
if ( ! function_exists( 'bws_bkng_get_page_permalink' ) ) {
	function bws_bkng_get_page_permalink( $slug, $extra = '' ) {
		global $bws_bkng;

		if ( ! in_array( $slug, $bws_bkng->get_booking_pages() ) ) {
			return false;
		}

		$page_link = get_page_link( $bws_bkng->get_option( "{$slug}_page" ) );

		return is_array( $extra ) ? add_query_arg( $extra, $page_link ) : $page_link;
	}
}

/**
 * Fetch the specified page type link for post type
 * @since    0.1
 * @param    string  $slug   The page type slug
 * @param    array   $extra  The list of additional link parameters
 * @return   string          The page type permalink
 */
if ( ! function_exists( 'bws_bkng_get_page_permalink_by_post_type' ) ) {
	function bws_bkng_get_page_permalink_by_post_type( $slug, $post_type, $extra = '' ) {
		global $bws_bkng;

		if ( /*! in_array( $slug, $bws_bkng->get_booking_pages() ) ||*/ empty( $post_type ) ) {
			return false;
		}
		$page_link = get_page_link( $bws_bkng->get_post_type_option( $post_type, "{$slug}_page" ) );

		return is_array( $extra ) ? add_query_arg( $extra, $page_link ) : $page_link;
	}
}

/**
 * Displays the specified page type link as HTML tag <a>
 * @since    0.1
 * @param    string  $slug   Page type slug
 * @return   void
 */
if ( ! function_exists( 'bws_bkng_page_link' ) ) {
	function bws_bkng_page_link( $slug, $extra = '' ) {
		global $bws_bkng;

		if ( ! in_array( $slug, $bws_bkng->get_booking_pages() ) )
			return false;

		$page_link = bws_bkng_get_page_permalink( $slug, $extra ); ?>

		<a class="bws_bkng_page_link bws_bkng_<?php echo esc_attr( $slug ); ?>_page_link " href="<?php echo esc_url( $page_link ); ?>"><?php echo get_the_title( $bws_bkng->get_option( "{$slug}_page" ) ); ?></a>
	<?php }
}

/**
 * Displays the last query search link
 * @since    0.1
 * @param    boolean  $get_previous    Whether to get the last search link or previously followed
 * @return   void
 */
if ( ! function_exists( 'bws_bkng_back_to_search_link' ) ) {
	function bws_bkng_back_to_search_link( $get_previous = false ) {

		$key = $get_previous ? 'previous_search_link' : 'last_search_link';

		$session = BWS_BKNG_Session::get_instance( true );
		$keys    = $session->get( $key );

		if ( empty( $keys ) )
			return; ?>

		<a class="bws_bkng_page_link bws_bkng_back_to_search_link " href="<?php echo esc_url( $session->get( $key ) ); ?>"><?php _e( 'Back to search', BWS_BKNG_TEXT_DOMAIN ); ?></a>

	<?php }
}

/**
 * Fetch the deafult thumbnail src
 * @since    0.1
 * @param    void
 * @return   string
 */
if ( ! function_exists( 'get_default_image_src' ) ) {
	function get_default_image_src() {
		global $bws_bkng;
		return $bws_bkng->get_default_image_src();
	}
}

/**
 * Fetch the deafult thumbnail HTML structure
 * @since    0.1
 * @param    string      The content of 'title' attribute
 * @param    string      The content of 'alt' attribute
 * @return   string      HTML tag <img> of the default plugin image thumbnail
 */
if ( ! function_exists( 'get_default_image' ) ) {
	function get_default_image( $title = '', $alt = '' ) {
		global $bws_bkng;
		return $bws_bkng->get_default_image( compact( 'title', 'alt' ) );
	}
}

/**
 * Outputs the wishlist toggle button
 * @since    0.1
 * @param    int      Post ID of element that will be affected | current post ID
 * @return   string   HTML
 */
if ( ! function_exists( 'bws_bkng_get_whishlist_btn' ) ) {
	function bws_bkng_get_wishlist_btn( $post_id = null ) {
	    global $bws_bkng;
	    if ( $bws_bkng->is_pro ) {
            if ( empty( $post_id ) ) {
                $post_id = get_the_ID();
            }
            $user = BWS_BKNG_User::get_instance();
            $in_wishlist = $user->is_in_wishlist( $post_id );
            if ( $in_wishlist ) {
                $text = __( 'Remove from Wishlist', BWS_BKNG_TEXT_DOMAIN );
            } else {
                $text = __( 'Add to Wishlist', BWS_BKNG_TEXT_DOMAIN );
            }
            ?>
            <form method="post" class="bkng_toggle_wishlist_form">
                <button type="submit" name="bkng_toggle_wishlist" class="button button-primary" data-in-wishlist="<?php echo $in_wishlist ? 'true' : 'false'; ?>"><?php echo esc_html( $text ); ?></button>
                <input type="hidden" name="bkng_post_ID" value="<?php echo esc_attr( $post_id ); ?>" />
                <?php wp_nonce_field( 'bkng_toggle_wishlist', 'bkng_nonce', true ); ?>
            </form>
        <?php }
    }
}

/**
 * Fetch the currency symbol according to the plugin settings
 * @since    0.1
 * @param    string
 * @param    string
 * @return   string
 */
if ( ! function_exists( 'bws_bkng_get_currency' ) ) {
	function bws_bkng_get_currency( $currency = null, $position = null ) {
		global $bws_bkng;

		$currency = is_null( $currency ) ? $bws_bkng->get_option( 'currency' ) : $currency;
		$position = is_null( $position ) || ! in_array( $position, array( 'left', 'right' ) ) ? $bws_bkng->get_option( 'currency_position' ) : $position;
		$currency = esc_html( $currency );

		return "<span class=\"bws_bkng_currency\">{$currency}</span>";
	}
}

/**
 * Fetch the currency position according to the plugin settings
 * @since    0.1
 * @param    void
 * @return   string     'left' or 'right'
 */
if ( ! function_exists( 'bws_bkng_get_currency_position' ) ) {
	function bws_bkng_get_currency_position() {
		global $bws_bkng;
		return $bws_bkng->get_option( 'currency_position' );
	}
}

/**
 * Prepare the numeric value before displaying it
 * @since    0.1
 * @param    int|float   $number
 * @param    array       $format
 * @return   string
 */
if ( ! function_exists( 'bws_bkng_number_format' ) ) {
	function bws_bkng_number_format( $number, $format = array() ) {
		global $bws_bkng;
		$format = wp_parse_args(
			(array)$format,
			array(
				'decimals' => $bws_bkng->get_option( 'number_decimals' ),
				'dec_sep'  => $bws_bkng->get_option( 'number_decimal_separator' ),
				'thou_sep' => $bws_bkng->get_option( 'number_thousand_separator' )
			)
		);
		extract( $format );
		return number_format( floatval( $number ), $decimals, $dec_sep, $thou_sep );
	}
}

if ( ! function_exists( 'bws_bkng_price_format' ) ) {
	function bws_bkng_price_format( $price, $wrap_class = "bws_bkng_product_price", $format = array() ) {
		global $bws_bkng;

		$format = wp_parse_args(
			(array)$format,
			array(
				'currency'          => $bws_bkng->get_option( 'currency' ),
				'currency_position' => $bws_bkng->get_option( 'currency_position' ),
				'decimals'          => $bws_bkng->get_option( 'number_decimals' ),
				'dec_sep'           => $bws_bkng->get_option( 'number_decimal_separator' ),
				'thou_sep'          => $bws_bkng->get_option( 'number_thousand_separator' )
			)
		);

		$price = '<span class="' . esc_attr( $wrap_class ) . '">' . bws_bkng_number_format( $price, $format ) . '</span>';
		$currency = bws_bkng_get_currency( $format['currency'] );
		return '<div class="bws_bkng_product_price_column">' . ( 'left' == $format['currency_position'] ? $currency . $price : $price . $currency ) . '</div>';
	}
}

/**
 * Fetch the list of attachment IDs bind to the currently viewed product.
 * @since    0.1
 * @param    void
 * @return   array
 */
if ( ! function_exists( 'bws_bkng_query_gallery' ) ) {
	function bws_bkng_query_gallery() {
		global $bws_bkng;

		if( $bws_bkng->is_booking_post() ) {

			$post = get_post();

			if ( empty( $post->ID ) )
				return false;

			$id = $bws_bkng->allow_variations && BWS_BKNG_VARIATION == $post->post_type && ! empty( $post->post_parent ) ? $post->post_parent  : $post->ID;

			$is_post = true;
		} elseif ( 'agencies' == $bws_bkng->is_booking_page() ) {
			$agency = bws_bkng_get_agency_data();

			if ( empty( $agency->term_id ) )
				return false;

			$id = absint( $agency->term_id );
			$is_post = false;
		} elseif ( is_author() ) {
			$user_class = BWS_BKNG_User::get_instance();

			$id = $user_class->gallery_id;
			$is_post = true;
		}

		if ( empty( $id ) ) {
			return false;
		}

		$gallery = new BWS_BKNG_Image_Gallery( $id, '', $is_post );

		return $gallery->get_image_ids();
	}
}

/**
 * Fetch the rent interval entered by user from the cookies.
 * @since    0.1
 * @param    void
 * @return   array
 */
if ( ! function_exists( 'bws_bkng_get_session_rent_interval' ) ) {
	function bws_bkng_get_session_rent_interval() {

		$timestamp = current_time( 'timestamp' );
		$next_day  = $timestamp + ( HOUR_IN_SECONDS - $timestamp % HOUR_IN_SECONDS ) + DAY_IN_SECONDS;
		$rent_step = absint( bws_bkng_get_rent_interval_step() );
		$session   = BWS_BKNG_Session::get_instance( true );
		$rent_inteval = $session->get( 'rent_interval' );

		if ( empty( $rent_step ) )
			$rent_step = DAY_IN_SECONDS;
		if ( empty( $rent_inteval['from'] ) || absint( $rent_inteval['from'] ) <= $timestamp ) {
			$from = $next_day;
			$force_till = true;
		} else {
			$from = absint( $rent_inteval['from'] );
			$force_till = false;
		}

		$till = empty( $rent_inteval['till'] ) || $force_till ? $from + $rent_step : absint( $rent_inteval['till'] );

		if ( $till <= $from ) {
			$from = $timestamp;
			$till = $from + $rent_step;
		}

		return compact( 'from', 'till' );
	}
}

/**
 * Fetch The list of errors that may occur due to some wrong data entered by user to the plugin forms
 * @since    0.1
 * @param    string     $type
 * @param    boolean    $return_codes
 * @return   void
 */
if ( ! function_exists( 'bws_bkng_get_errors' ) ) {
	function bws_bkng_get_errors( $type = '', $return_codes = false ) {
		global $bws_bkng;
		$type   = esc_attr( $type );

		switch ( $type ) {
			case 'checkout':
				$order  = BWS_BKNG_Order::get_instance();
				$errors = $order->get_errors();
				break;
			case 'cart':
				$cart   = BWS_BKNG_Cart::get_instance();
				$errors = $cart->get_errors();
				break;
			default:
				$errors = apply_filters( 'bws_bkng_errors', '', $type );
				break;
		}

		if ( $return_codes && is_wp_error( $errors ) )
			return (array)$errors->get_error_codes();

		return $errors;
	}
}

/**
 * Checks whether the error (specified in $error_keys) occured
 * @since    0.1
 * @param    array     The list of searched errors slugs.
 * @param    array     The list of errors.
 * @return   void
 */
if ( ! function_exists( 'bws_bkng_is_error' ) ) {
	function bws_bkng_is_error( $error_keys, $errors ) {
		return array_intersect( (array)$error_keys, (array)$errors );
	}
}

/**
 * Displays the errors
 * @since    0.1
 * @param    string
 * @return   void
 */
if ( ! function_exists( 'bws_bkng_errors' ) ) {
	function bws_bkng_errors( $type = '' ) {
		global $bws_bkng;
		$type   = esc_attr( $type );
		$errors = bws_bkng_get_errors( $type );
		$class  = empty( $errors ) ? BWS_BKNG::$hidden : '';
		echo $bws_bkng->get_errors( $errors, '', $class );
	}
}

/**
 * Checks whether there is a need to display the "Register me" checkbox
 * on checkout page
 * @since    0.1
 * @param    void
 * @return   boolean
 */
if ( ! function_exists( 'bws_bkng_show_register_checkbox' ) ) {
	function bws_bkng_show_register_checkbox() {
		global $bws_bkng;

		return 'user' == $bws_bkng->get_option( 'checkout_registration' ) && get_option( 'users_can_register' );
	}
}

/**
 * Displays the map
 * @since    0.1
 * @param    array       $data       The data to be used to display the map
 * @return   void
 */
if ( ! function_exists( 'bws_bkng_get_map_data' ) ) {
	function bws_bkng_get_map_data( $data ) {
		global $bws_bkng;

		$default = array(
			'label'     => __( 'Location', BWS_BKNG_TEXT_DOMAIN ),
			'address'   => $bws_bkng->get_option( 'google_map_default_address' ),
			'longitude' => $bws_bkng->get_option( 'google_map_default_lng' ),
			'latitude'  => $bws_bkng->get_option( 'google_map_default_lat' ),
			'id'        => 'bws_bkng',
			'display'   => false
		);

		$data = wp_parse_args( $data, $default );
		$id   = bws_bkng_sanitize_id( $data['id'] ); ?>

		<?php if ( ! empty( $data['label'] ) ) { ?>
			<div class="bws_bkng_column bkng_meta_label">
				<?php echo esc_html( "{$data['label']}:&nbsp;" ); ?>
			</div>
		<?php } ?>
		<div class="bws_bkng_column bkng_meta_value">
			<?php echo esc_html( "{$data['address']}&nbsp;" ); ?>
			<a href="#" data-target="<?php echo esc_attr( $id ); ?>_map" class="dashicons dashicons-location-alt bws_bkng_show_map_link" title="<?php _e( 'Show on map', BWS_BKNG_TEXT_DOMAIN ); ?>" data-label="<?php _e( 'Hide map', BWS_BKNG_TEXT_DOMAIN ); ?>" data-display="<?php echo esc_attr( $data['display'] ); ?>"></a>
		</div>
		<div class="bws_bkng_map_wrap"
			id="<?php echo esc_attr( $id ); ?>_map"
			data-lng="<?php echo esc_attr( $data['longitude'] ); ?>"
			data-lat="<?php echo esc_attr( $data['latitude'] ); ?>"></div>
	<?php }
}


/**
 * Generates the unique HTML attribute 'id' value.
 * @uses in order to avoid more than one the html-block ID on the page and possible JS-errors because of that.
 * The issue occurs when the search form is displayed couple times (eg. via BWS Slider)
 * @since    0.1
 * @param    string       $id       The raw id
 * @return   string
 */
if ( ! function_exists( 'bws_bkng_sanitize_id' ) ) {
	function bws_bkng_sanitize_id( $id = '' ) {
		static $ids_counter;

		$id = sanitize_html_class( $id );

		if ( empty( $id ) )
			$id = "bws_bkng";

		if ( empty( $ids_counter ) )
			$ids_counter = array();

		if ( empty( $ids_counter[ $id ] ) ) {
			$unique_id = $id;
			$ids_counter[ $id ] = 1;
		} else {
			$unique_id = "{$id}_{$ids_counter[ $id ]}";
			$ids_counter[ $id ] ++;
		}
		return $unique_id;
	}
}

/**
 * Outputs payment method select for pro version
 * @since    0.1
 * @return   string HTML
 */
if ( ! function_exists( 'bws_bkng_get_payment_methods' ) ) {
    function bws_bkng_get_payment_methods() {
        global $bws_bkng;
        if ( $bws_bkng->is_pro ) {
            $billing_data 	= bws_bkng_get_billing_data();
            $errors       	= bws_bkng_get_errors( 'checkout', true ); ?>
            <div class="bwspattenr-userinfo-block">
                <div class="container">
                    <h4 class="bwspattern-title"><?php _e( 'Payment method', 'rent-a-bike' ); ?></h4>
                    <p class="bwspattern-text-info py-3"><?php _e( 'Please choose your payment method!', 'rent-a-bike' ); ?></p>
                    <?php do_action( 'bws_bkng_checkout_form_before_submit_button', $billing_data, $errors );?>
                </div>
            </div>
        <?php }
    }
}
