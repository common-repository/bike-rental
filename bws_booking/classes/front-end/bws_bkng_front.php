<?php
/**
 * Init the core front-end functionality
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Front' ) )
	return;

class BWS_BKNG_Front {

	private $is_one_category;

	/**
	 * Constructor of class
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function __construct() {
		global $bws_bkng;
		$this->is_one_category = $bws_bkng->is_only_one_category();
		$this->add_rewrite_rules();

		add_action( 'wp', array( $this, 'init_observer' ) );
		add_filter( 'posts_results', array( $this, 'add_product_data' ) );
		add_filter( 'template_include', array( $this, 'add_content_filters' ), 100 );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ) );
		add_filter( 'body_class', array( $this, 'add_body_classes' ) );
		add_filter( 'redirect_canonical', array( $this, 'maybe_prevent_redirect' ), 10, 2 );
	}

	/**
	 * Prevent the redirect to the home page from the WP core
	 * in order to get the necessary query parameters for the plugin work
	 * @see wp-includes/canonical.php, the function redirect_canonical() definition
	 * @since  0.1
	 * @access public
	 * @param  string   $redirect_url     The redirect URL.
	 * @param  string   $requested_url    The requested URL.
	 * @return string|boolean             false - in case if one of the plugin pages was set as home page,
	 *                                    the redirect URL otherwise
	 */
	public function maybe_prevent_redirect( $redirect_url, $requested_url = '' ) {
		global $bws_bkng;
		return is_front_page() && 'page' == get_option( 'show_on_front' ) && $bws_bkng->is_booking_page() ? false : $redirect_url;
	}

	/**
	 * Adds rewrite rules
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function add_rewrite_rules() {
		global $bws_bkng, $wpdb, $bws_post_type, $bws_admin_menu_pages;

		$current_post_types = array_keys( $bws_post_type );
		$agency_pages = $products_pages = array();

		/*
		 * Add rewrite tags in order to get them from the  $wp_query object
		 */
		$tags = array( 'search', 'bkng_tax', 'bkng_term', BWS_BKNG_AGENCIES, BWS_BKNG_CATEGORIES, BWS_BKNG_TAGS );
		foreach ( $tags as $tag ) {
			add_rewrite_tag( "%{$tag}%", '([^&]+)' );
		}

		if ( get_option( 'permalink_structure' ) ) {
			foreach( $products_pages as $products_page ) {
				$products_page_slug = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT `post_name` FROM `{$wpdb->posts}` WHERE `ID`=%d",
                        $products_page
                    )
                );
				$this->rewrite_rules( $products_page_slug, $products_page );
			}
			foreach( $agency_pages as $agency_page ) {
				$agency_page_slug = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT `post_name` FROM `{$wpdb->posts}` WHERE `ID`=%d",
                        $products_page
                    )
                );
				$this->rewrite_rules( $agency_page_slug, $agency_page );
			}
		}
	}

	private function rewrite_rules( $page_slug, $page_id ) {

			/* Just paginated page without any parameters */
			add_rewrite_rule( "^{$page_slug}/page/([\d]+)/?$", "index.php?page_id={$page_id}&paged=\$matches[1]", 'top' );

			/* The paginated page by the chosen term */
			add_rewrite_rule( "^{$page_slug}/([^/]+)/page/([\d]+)/?$", "index.php?page_id={$page_id}&{$page_slug}=\$matches[1]&paged=\$matches[2]", 'top' );

			/* The products ( without the pagination ) page by the chosen term and without any additional paramenetrs */
			add_rewrite_rule( "^{$page_slug}/([^/]+)/?$", "index.php?page_id={$page_id}&{$page_slug}=\$matches[1]", 'top' );
	}

	/**
	 * Check the template before including.
	 * @see    https://wphierarchy.com/, https://developer.wordpress.org/themes/basics/template-hierarchy/
	 * @since  0.1
	 * @access public
	 * @param  string  $template    The template file source
	 * @return string  $template    The template file source
	 */
	public function add_content_filters( $template ) {
		global $bws_bkng;

		if ( is_feed() )
			return $template;

		$template_name = basename( $template, ".php" );
		$booking_page  = $bws_bkng->is_booking_page();

		/* Single products page */
		if ( $bws_bkng->is_booking_post() && $bws_bkng->is_primary_template( $template_name, 'single' ) ) {
			$page = 'single_product';
		/* It is the one of plugin pages */
		} elseif ( $booking_page && $bws_bkng->is_primary_template( $template_name, 'page' ) ) {
			$page = $booking_page;
		} else {
			$page = apply_filters( 'bws_bkng_page', '', $template );
		}

		/**
		 * Filters won't be added if there isn't the plugin page or
		 * the custom theme page template is used to display content.
		 */
		if ( ! empty( $page ) || apply_filters( 'bws_bkng_force_content_filters_init', false ) ) {

			$filter = BWS_BKNG_Content_Filter::get_instance();

			if ( $bws_bkng->allow_variations )
				$filter->add_variation_filters();

			$filter->add_content_filters( $page );
		}

		return $template;
	}

	/**
	 * Adds additional meta-data for products
	 * @since  0.1
	 * @access public
	 * @param  array  $posts    The posts ( WP_POST objects ) list
	 * @return array  $posts    The posts ( WP_POST objects ) list
	 */
	public function add_product_data( $posts ) {
		global $bws_bkng;


		foreach ( $posts as $post ) {

			if ( ! in_array( $post->post_type, $bws_bkng->get_post_types() ) )
				continue;

			$post->bkng_product_status   = get_post_meta( $post->ID, 'bkng_product_status', true );
			$post->bkng_category_id      = $bws_bkng->get_product_category( 'term_id', $post );
			$post->bkng_is_countable     = get_post_meta( $post->ID, 'bkng_quantity_available', true );
			$post->bkng_in_stock         = get_post_meta( $post->ID, 'bkng_in_stock', true );
			$post->bkng_price_on_request = get_post_meta( $post->ID, 'bkng_price_on_request', true );
		}

		return $posts;
	}

	/**
	 * Monitors th user actions in the front-end and takes the necessary response actions
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function init_observer() {
		global $bws_bkng, $post;
		$booking_page = $bws_bkng->is_booking_page();
		$booking_post = $bws_bkng->is_booking_post();
		if ( ! $booking_post && ! $booking_page )
			return;

		$session       = BWS_BKNG_Session::get_instance( true );
		$rent_interval = $session->get( 'rent_interval' );
		$now           = current_time( 'timestamp' );

		if ( ! empty( $rent_interval['from'] ) && ( absint( $rent_interval['from'] ) <= $now || absint( $rent_interval['till'] ) <= $now ) )
			$session->remove( 'rent_interval' );

		switch ( $booking_page ) {
			case 'products':
				$query = bws_bkng_get_query();
				/* If the user came to the current page by sending search request */
				if ( ! empty( $query['search'] ) && 2 == count( array_intersect( array_keys( $query['search'] ), array( 'from', 'till' ) ) ) ) {

					if ( $bws_bkng->get_current_url() != $session->get( 'last_search_link' ) )
						$session->update( 'previous_search_link', $session->get( 'last_search_link' ) );

					$session->update( 'last_search_link', $bws_bkng->get_current_url() );
					$session->update( 'rent_interval', array(
						'from' => $query['search']['from'],
						'till' => $query['search']['till']
					) );
				}
				break;
			case 'checkout':
				$order_handler = BWS_BKNG_Order::get_instance();
				$order = $order_handler->get();

				if ( ! is_user_logged_in() && 'yes' == $bws_bkng->get_option( 'checkout_registration' ) && get_option( 'users_can_register' ) ) {

					/* Remember the order data to display it after user come back */
					if ( $order )
						$session->update( 'order', $order );

					wp_redirect( wp_login_url( get_permalink() ) );
					exit();
				}
				if ( $order_handler->is_place_order_query() ) {
					if ( $order_handler->is_right_billing_data() ) {

						$new_order_id = $order_handler->place_order();

						if ( ! $new_order_id )
							break;

						if ( apply_filters( 'bws_bkng_send_new_order_notes', true ) ) {
							$mailer = new BWS_BKNG_Mailer();
							$args   = array(
								'id'     => $new_order_id,
								'status' => 'on_hold',
								'send'   => 'both'
							);
							$mailer->send_order_notes( $args );
						}

						$billing_data = bws_bkng_get_billing_data();

						$session->remove( 'order' );
						$session->remove( 'last_search_link' );
						$session->remove( 'rent_interval' );

						/**
						 * Remember entered data for further autofill
						 */
						$session->update( 'billing_data', $billing_data );

						/**
						 * In order to prevent brute force attack to this page add hashed string with user ID and last order ID
						 * The user will be able to vieew its last order by the link {link_to_thanky_page}/?bkng_order={order_ID}
						 * until he close the browser window
						 */
						$session->add( 'last_order_hash', hash_hmac( 'ripemd160', $new_order_id, "user_{$billing_data['user_id']}" ) );
						$redirect_url = add_query_arg( 'bkng_order', $new_order_id, bws_bkng_get_page_permalink( 'thank_you' ) );
						wp_redirect( $redirect_url );
						exit();
					}
				} else {
					/* Remember the order to display it in case if the left the page for some reasons and then came back */
					if ( $order )
						$session->update( 'order', $order );
				}
				break;
			default:
				break;
		}

		/**
		 * Adds the product to the cart in case if JS is disabled
		 * This functionality is temporary commented due to the fact that the cart functionality isn't over yet
		 * @todo uncomment, check (and make corrections) after the cart functionality will be completed
		 */
		// $cart = BWS_BKNG_Cart::get_instance();
		// if (
		// 	$booking_post &&
		// 	isset( $_REQUEST['bkng_action'] ) &&
		// 	'add' == $_REQUEST['bkng_action'] &&
		// 	! empty( $_REQUEST['bkng_product'] ) &&
		// 	wp_verify_nonce( $_REQUEST['bkng_nonce'], "bkng_add_{$post->ID}" )
		// ) {
		// 	$data = array(
		// 		$_REQUEST['bkng_product'] => array(
		// 			'linked_to'     => empty( $_REQUEST['bkng_linked_to'] ) ? ''    : absint( $_REQUEST['bkng_linked_to'] ),
		// 			'quantity'      => empty( $_REQUEST['bkng_quantity'] )  ? false : absint( $_REQUEST['bkng_quantity'] ),
		// 			'rent_interval' => array(
		// 				'from' => empty( $_REQUEST['bkng_from'] ) ? '' : absint( $_REQUEST['bkng_from'] ),
		// 				'till' => empty( $_REQUEST['bkng_till'] ) ? '' : absint( $_REQUEST['bkng_till'] )
		// 			)
		// 		)
		// 	);
		// 	$cart->add( $data );
		// 	wp_redirect( get_the_permalink() );
		// }

	}

	/**
	 * Adds JS-scripts and CSS-rules to the site pages
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function add_scripts() {
		global $bws_bkng, $wp_locale, $bws_post_type, $bws_admin_menu_pages;

		/**
		 * Include plugin styles
		 */
		wp_enqueue_style( 'bkng_jquery_ui_style', BWS_BKNG_URL . "css/jquery.ui.css" );
		wp_enqueue_style( 'bkng_front_style', BWS_BKNG_URL . "css/front_style.css", array( 'dashicons' ) );

		foreach( array( 'catalog_image_size', 'thumbnails_image_size' ) as $size ) {
			$current_post_types = array_keys( $bws_post_type );
			foreach( $current_post_types as $post_type ) {
				if( isset( $bws_admin_menu_pages[ $post_type ]['settings_page'] ) ) {
					$$size = $bws_bkng->get_post_type_option( $post_type, $size );
				}
			}
		}
		
		$styles = '';
		if ( is_admin_bar_showing() && $bws_bkng->get_option( 'enable_lightbox' ) ) {
			$styles .= "\n.fancybox-container {
				margin-top: 32px;
			}
			@media screen and (max-width: 782px) {
				.fancybox-container {
					margin-top: 46px;
				}
			}
			@media screen and (max-width: 600px) {
				.fancybox-container {
					margin-top: 0;
				}
			}";
		}

		wp_add_inline_style( 'bkng_front_style', $styles ); ?>

		<noscript>
			<style type="text/css">
				.bkng_hide_if_no_js {
					display: none !important;
				}
				.bkng_show_if_no_js {
					display: block !important;
				}
			</style>
		</noscript>

		<?php foreach( $current_post_types as $post_type ) {
			if( isset( $bws_admin_menu_pages[ $post_type ]['settings_page'] ) ) {
				if ( $bws_bkng->get_post_type_option( $post_type, 'enable_lightbox' ) ) {
					BWS_BKNG_Image_Gallery::enque_scripts();
					break;
				}
			}
		}

		$bws_bkng->add_google_map_scripts();
		$bws_bkng->add_datepicker_scripts();

		$data = array(
			'time_format'                => get_option( 'time_format' ),
			'date_format'                => '' != $bws_bkng->get_option( 'date_format' ) ? str_replace( '_', ' ', $bws_bkng->get_option( 'date_format' ) ) : 'Y/m/d',
			'is_custom_permalinks'       => !!get_option( 'permalink_structure' ),
			'home_url'                   => get_home_url(),
			'category'                   => BWS_BKNG_CATEGORIES,
			'hidden'                     => BWS_BKNG::$hidden,
			'wrong_date'                 => __( 'Please set correct date range', BWS_BKNG_TEXT_DOMAIN ),
			'past_date'                  => __( "You can't select a past date", BWS_BKNG_TEXT_DOMAIN ),
			'category_not_selected'      => __( 'Please select the category', BWS_BKNG_TEXT_DOMAIN ),
			'location_not_selected'      => __( 'Please enter a destination to start searching', BWS_BKNG_TEXT_DOMAIN ),
			'choose_extras'              => __( 'Please choose some extras', 'bws_bkng' ),
			'products_page'              => $bws_bkng->get_option( 'products_page' ),
			'dec_sep'                    => $bws_bkng->get_option( 'number_decimal_separator' ),
			'thou_sep'                   => $bws_bkng->get_option( 'number_thousand_separator' ),
			'currency'                   => $bws_bkng->get_option( 'currency' ),
			'currency_position'          => $bws_bkng->get_option( 'currency_position' ),
			'ajaxurl'                    => admin_url( 'admin-ajax.php' ),
			'is_single'                  => $bws_bkng->is_booking_post(),
			'is_rtl'                     => is_rtl(),
			'rent_interval'              => bws_bkng_get_datetimepicker_data(),
			'locale'                     => preg_replace( '/^([^-_]*)(.*?)$/','$1', get_locale() )
		);

		wp_register_script( 'bkng_general_script', BWS_BKNG_URL . 'js/general.js' );
		wp_enqueue_script( 'bkng_front_script', BWS_BKNG_URL . 'js/front_script.js', array( 'jquery', 'jquery-ui-slider', 'bkng_general_script' ), false, true );
		wp_localize_script( 'bkng_front_script', 'bws_bkng', $data );
	}

	/**
	 * Adds classes for the tag <body> in order to style plugin pages for the concrete theme
	 * @since  0.1
	 * @access public
	 * @param  array     $classes    The list of the <body> classes
	 * @return array     $classes
	 */
	public function add_body_classes( $classes ) {
		global $bws_bkng;
		$booking_page = $bws_bkng->is_booking_page();

		if ( $booking_page )
			$classes[] = "bws_bkng_page_{$booking_page}";

		$classes[] = "bws_bkng_theme_" . get_option('stylesheet');
		return $classes;

	}

}
