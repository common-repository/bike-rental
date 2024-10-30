<?php
/**
 * Adds necessary content filters in order to manage the process of booking.
 * Loads plugin's templates
 *
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Content_Filter' ) )
	return;

class BWS_BKNG_Content_Filter {

	/**
	 * Contains an instance of the current class
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    object
	 */
	private static $instance;

	/**
	 * Contains the page type slug
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $page;

	/**
	 * Fetch the class instance
	 * @since    0.1
	 * @access   public
	 * @static
	 * @param    void
	 * @return   object           An instance of the current class
	 */
	public static function get_instance() {

		if ( ! self::$instance instanceof self )
			self::$instance = new self();

		return self::$instance;
	}

	/**
	 * Add necessary filters in order to replace the variation data with the parent product data
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function add_variation_filters() {
		add_filter( 'get_post_metadata', array( $this, 'get_parent_thumbnail_id' ), 10, 3 );
		add_filter( 'post_thumbnail_html', array( $this, 'get_default_thumbnail' ), 10, 2 );
		add_filter( 'get_the_excerpt', array( $this, 'get_parent_excerpt' ), 10, 2 );
		add_filter( 'pre_get_document_title', array( $this, 'get_parent_title' ) );
		add_filter( 'the_title', array( $this, 'get_parent_title' ), 10, 2 );
	}

	/**
	 * Adds the main content filters
	 * @since  0.1
	 * @access public
	 * @param  string  $page
	 * @return void
	 */
	public function add_content_filters( $page ) {
		global $bws_bkng;

		$this->page = $page;

		if (
			in_array( $page, $bws_bkng->get_booking_pages() ) &&
			method_exists( $this, "{$page}_page" )
		) {
			add_filter( 'the_content', array( $this, "{$page}_page" ) );
			add_filter( 'the_excerpt', array( $this, "{$page}_page" ) );
		} elseif ( 'single_product' == $page ) {
			add_filter( 'the_content', array( $this, "{$page}_page" ) );
		} else {
			do_action( 'bws_bkng_content_filter_{$page}', $this::$instance );
		}
	}

	/**
	 * Displays the list of attributes, product gallery and the extras list on the single product page
	 * @since  0.1
	 * @access public
	 * @param  string  $content     The product content
	 * @return string  $content     The product content
	 */
	public function single_product_page( $content ) {
		remove_filter( 'the_content', array( $this, __FUNCTION__ ) );

		if ( post_password_required() )
			return $content;

		$content = $this->get_maybe_parent_content( $content );

		return
			'<div ' . bws_bkng_get_product_classes() . '>' .
				$this->get_template_parts( 'price', 'forms/to-checkout-button', 'lists/taxonomies', 'product-attributes' ) .
				( empty( $content ) ? '' : '<h3>' . __( 'Description', BWS_BKNG_TEXT_DOMAIN ) . '</h3>' . $content ) .
				$this->get_template_parts( 'gallery', 'forms/extras' ) .
			'</div>';
	}

	/**
	 * Add primary search items in order to select the products category
	 * @since  0.1
	 * @access public
	 * @param  string  $content     The page content
	 * @return string  $content     The page content
	 */
	public function products_page( $content ) {
		remove_filter( 'the_content', array( $this, __FUNCTION__ ) );
		remove_filter( 'the_excerpt', array( $this, __FUNCTION__ ) );

		if ( post_password_required() )
			return $content;

		$query = bws_bkng_get_query();
		if ( ! empty( $query[ BWS_BKNG_CATEGORIES ] ) || ! empty( $query["bkng_tax"] ) ) {

			$template = 'lists/products';

		} else {
			if( ! empty( bws_bkng_get_categories() ) ) {
				switch ( count( bws_bkng_get_categories() ) ) {
					/* If there is no products categories that are used for primary search */
					case 0:
						$template = 'none';
						break;
					/* If there is only one product category */
					case 1:
						$template = 'lists/products';
						break;
					/* In any other cases */
					default:
						$template = apply_filters( 'bws_bkng_display_categories_as_form', true ) ? 'forms/search-products' : 'lists/categories';
						break;
				}
			} else {
				$template = 'lists/products';
			}
		}

		return $content . $this->get_template_parts( $template );
	}

	/**
	 * Adds order details and checkout form
	 * @since  0.1
	 * @access public
	 * @param  string  $content     The page content
	 * @return string  $content     The page content
	 */
	public function checkout_page( $content ) {

		global $bws_bkng;

		remove_filter( 'the_content', array( $this, __FUNCTION__ ) );
		remove_filter( 'the_excerpt', array( $this, __FUNCTION__ ) );

		if ( post_password_required() )
			return $content;

		if ( ! is_user_logged_in() && 'yes' == $bws_bkng->get_option( 'checkout_registration' ) && ! get_option( 'users_can_register' ) )
			return __( 'Product ordering is available only for registered users', BWS_BKNG_TEXT_DOMAIN );

		return $content . $this->get_template_parts( 'order-details', 'forms/checkout' );
	}

	/**
	 * Displays the list of products in the cart
	 * @since  0.1
	 * @access public
	 * @param  string  $content    The page content
	 * @return string  $content    The page content
	 */
	public function cart_page( $content ) {
		remove_filter( 'the_content', array( $this, __FUNCTION__ ) );
		remove_filter( 'the_excerpt', array( $this, __FUNCTION__ ) );

		if ( post_password_required() )
			return $content;

		return $content . $this->get_template_parts( 'forms/cart' );
	}

	/**
	 * Displays the order data after placing the order
	 * @since  0.1
	 * @access public
	 * @param  string  $content     The page content
	 * @return string  $content     The page content
	 */
	public function thank_you_page( $content ) {
		remove_filter( 'the_content', array( $this, __FUNCTION__ ) );
		remove_filter( 'the_excerpt', array( $this, __FUNCTION__ ) );

		if ( post_password_required() )
			return $content;

		$order_id = bkng_get_order_id();

		if ( empty( $order_id ) )
			return $content;

		/* Prevents unauthorized view of orders */
		$session  = BWS_BKNG_Session::get_instance( true );
		$token    = $session->get( 'last_order_hash' );
		$hash     = hash_hmac( 'ripemd160', $order_id, "user_" . get_current_user_id() );

		if( 0 !== strcasecmp( $token, $hash ) )
			return $content;

		return $content . $this->get_template_parts( 'order-details' );
	}

	/**
	 * Displays the user profile page
	 * @since  0.1
	 * @access public
	 * @param  string  $content     The page content
	 * @return string  $content     The page content
	 *
	 * @todo For this moment development of this functionality is not over yet.
	 *       Uncomment after it completion.
	 */
	// public function user_account_page( $content ) {
	// 	global $bws_bkng;
	// 	if ( is_user_logged_in() )
	// 		$templates = 'user-profile';
	// 	elseif ( $bws_bkng->get_option( 'account_registration' ) )
	// 		$templates = array( 'forms/login', 'forms/register' );

	// 	if ( ! empty( $templates ) )
	// 		$content .= $this->get_template_parts( $templates );

	// 	return $content;
	// }

	/**
	 * Displays the list of agents
	 * @since  0.1
	 * @access public
	 * @param  string  $content     The page content
	 * @return string  $content     The page content
	 *
	 * @todo For this moment development of this functionality is not over yet.
	 *       Uncomment after it completion.
	 */
	// public function agents_page( $content ) {
	// 	return $content . $this->get_template_parts( 'lists/agents' );
	// }

	/**
	 * Displays the list of agencies
	 * @since  0.1
	 * @access public
	 * @param  string  $content     The page content
	 * @return string  $content     The page content
	 */
	public function agencies_page( $content ) {
		global $wp_query;

		if ( get_option( 'permalink_structure' ) )
			$template = empty( $wp_query->query[ BWS_BKNG_AGENCIES ] ) ? 'lists/agencies' : 'single-agency';
		else
			$template = empty( $_GET['agency'] ) ? 'lists/agencies' : 'single-agency';

		return $content . $this->get_template_parts( $template );
	}

	/**
	 * Loads templates and returns its content
	 * @since  0.1
	 * @access public
	 * @param  string|array  $templates     The list of templates
	 * @return string        $content_part  The templates content
	 */
	public function get_template_parts( $templates ) {

		$templates = is_array( $templates ) ? $templates : func_get_args();
		$templates = apply_filters( 'bws_bkng_template_parts', $templates, $this->page );

		ob_start();

		foreach ( (array)$templates as $template )
			bws_bkng_get_template_part( $template );

		$content_part = ob_get_clean();

		return $content_part;
	}

	/**
	 * Fetch default product featured image in case if it is not set
	 * @see    get_the_post_thumbnail()
	 * @since  0.1
	 * @access public
	 * @param  string       $html              The post thumbnail HTML
	 * @param  int          $post_id           The post ID
	 * @return string       $html              The post thumbnail image tag
	*/
	public function get_default_thumbnail( $html, $post_id ) {

		if ( ! empty( $html ) )
			return $html;

		$post = get_post( $post_id );

		if ( ! $post || ! in_array( $post->post_type, array( BWS_BKNG_POST, BWS_BKNG_VARIATION ) ) )
			return $html;

		if ( BWS_BKNG_VARIATION == $post->post_type && ! empty( $post->post_parent ) )
			return get_the_post_thumbnail( $post->post_parent );

		return empty( $html ) ? get_default_image() : $html ;

	}

	/**
	 * Fetch parent product thumbnail ID
	 * @see    get_post_thumbnail_id(), get_post_meta(), get_metadata()
	 * @since  0.1
	 * @access public
	 * @param null|array|string   $check       The value get_metadata() should return -
	 *                                           a single metadata value, or an array of values
	 * @param int                 $object_id   Object ID
	 * @param string              $meta_key    Meta key
	 */
	public function get_parent_thumbnail_id( $check, $object_id, $meta_key ) {

		if ( '_thumbnail_id' != $meta_key )
			return $check;

		$post = get_post( $object_id );

		if ( ! $post )
			return $check;

		if ( BWS_BKNG_VARIATION == $post->post_type && ! empty( $post->post_parent ) )
			return get_post_meta( $post->post_parent, '_thumbnail_id', true );

		return $check;
	}

	/**
	 * Fetch the parent product excerpt
	 * @since  0.1
	 * @access public
	 * @param  string               $excerpt     The post excerpt
	 * @param  WP_Post|int|null     $post        The current post data
	 * @return string               $excerpt
	 */
	public function get_parent_excerpt( $excerpt, $post = null ) {

		$post = get_post( $post );

		if ( empty( $post->post_type ) || BWS_BKNG_VARIATION != $post->post_type || empty( $post->post_parent ) )
			return $excerpt;

		return get_the_excerpt( $post->post_parent );
	}

	/**
	 * Fetch the parent product content
	 * @since  0.1
	 * @access public
	 * @param  string  $content     The page content
	 * @return string  $content     The page content
	 */
	public function get_maybe_parent_content( $content ) {
		global $bws_bkng;

		if ( ! $bws_bkng->allow_variations )
			return $content;

		$post = get_post();

		if ( BWS_BKNG_VARIATION !== $post->post_type || empty( $post->post_parent ) )
			return $content;

		$parent = get_post( $post->post_parent );

		return apply_filters( 'the_content', $parent->post_content );

	}

	/**
	 * Fetch parent product title
	 * @see    get_the_title()
	 * @since  0.1
	 * @access public
	 * @param  string $title       The post title.
	 * @param  int     $post_id    The post ID.
	 * @return string              The post title.
	 */
	public function get_parent_title( $title, $post_id = null ) {

		$post = get_post( $post_id );

		if ( empty( $post ) || BWS_BKNG_VARIATION != $post->post_type || empty( $post->post_parent ) )
			return $title;

		return get_the_title( $post->post_parent );
	}

	/**
	 * Some magic to avoid the creation of several instances of this class
	 * @since  0.1
	 */
	private function __construct() {}
	private function __clone()     {}
	private function __sleep()     {}
	private function __wakeup()    {}
}