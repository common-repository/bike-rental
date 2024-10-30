<?php
/**
 * Handles the Booking entities galleries
 *
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Image_Gallery' ) )
	return;

class BWS_BKNG_Image_Gallery {

	/**
	 * The object ID
	 *
	 * @since  0.1
	 * @access private
	 * @var int
	 */
	private $id;

	/**
	 * The tag's <input> attribute 'name' value
	 * @uses on the edit gallery pages in admin panel
	 * @since  0.1
	 * @access private
	 * @var string
	 */
	private $input_name;

	/**
	 * Wheter the object is the class WP_Post instance
	 *
	 * @since  0.1
	 * @access private
	 * @var string
	 */
	private $is_post;

	/**
	 * Class constructor
	 * @since  0.1
	 * @access public
	 * @param  int        $id             The object ID
	 * @param  string     $input_name     The tag's <input> attribute 'name' value
	 * @param  boolean    $is_post        Wheter the object is the product
	 * @return void
	 */
	public function __construct( $id, $input_name = '', $is_post = true ) {
		$this->id         = absint( $id );
		$this->input_name = esc_attr( $input_name );
		$this->is_post    = !! $is_post;
	}

	/**
	 * Saves the gallery images list
	 *
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function save_images() {
		$fields = array( BWS_BKNG_POST . '_images', BWS_BKNG_POST . '_featured_image' );
		$func   = $this->is_post ? 'update_post_meta' : 'update_term_meta';
		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $this->input_name ][ $field ] ) ) {
				$ids = array_unique( array_filter( explode( ',', sanitize_text_field( stripslashes( $_POST[ $this->input_name ][ $field ] ) ) ), array( $this, 'exclude_empty' ) ) );
				$func( $this->id, $field, implode( ',', $ids ) );
			}
		}
	}

	/**
	 * Sanitizes the images list
	 *
	 * @uses   self::save_images()
	 * @since  0.1
	 * @access public
	 * @param  int    $id   The image ID
	 * @return boolean
	 */
	public function exclude_empty( $id ) {
		return !! absint( $id );
	}

	/**
	 * Fetch the list of images ID bind to the gallery
	 *
	 * @since  0.1
	 * @access public
	 * @param  boolean   $is_featured_image    Whether the displayed type is a featured image (true) or a gallery (false)
	 * @return array     The list of image ids
	 */
	public function get_image_ids( $is_featured_image = false ) {
		global $bws_bkng;
		$field = BWS_BKNG_POST . ( $is_featured_image ? '_featured_image' : '_images' );
		$func  = $this->is_post ? 'get_post_meta' : 'get_term_meta';
		$value = $func( $this->id, $field, true );
		return array_unique( array_filter( explode( ',', $value ) ) );
	}

	/**
	 * Displays the gallery in the site admin panel
	 *
	 * @since  0.1
	 * @access public
	 * @param  boolean    $is_featured_image    Whether the displayed type is a featured image (true) or a gallery (false)
	 * @return string     The HTML-content
	 */
	public function get_content( $is_featured_image = false, $wrap_class = 'bkng_post_gallery_wrap postbox' ) {
		global $bws_bkng;
		$field     = BWS_BKNG_POST . ( $is_featured_image ? '_featured_image' : '_images' );
		$image_ids = $this->get_image_ids( $is_featured_image );

		$added   = $images = array();
		$items   = '';
		$id_list = '';
		$data_single = $is_featured_image ? ' data-single="1"' : '';

		/**
		 * "Delete image" button
		 */
		$unit   = 'button';
		$class  = "bkng_delete_image dashicons dashicons-trash";
		$attr   = 'title="' . esc_attr__( 'Delete media', BWS_BKNG_TEXT_DOMAIN ). '"';
		$attr   .= ' style="font-family: dashicons;"';
		$delete_button = $bws_bkng->get_form_unit( compact( 'unit', 'class', 'attr' ) );

		/**
		 * "Add media" link
		 */
		$class  = "bkng_add_image button " . $bws_bkng::$hide_if_no_js;

		if ( $is_featured_image ){
			$text  = empty( $images ) ? __( 'Set featured image', BWS_BKNG_TEXT_DOMAIN ) : __( 'Update featured image', BWS_BKNG_TEXT_DOMAIN );
			$pop_up_title = __( 'Select featured image', BWS_BKNG_TEXT_DOMAIN );
			$button_title = __( 'Select', BWS_BKNG_TEXT_DOMAIN );
		} else {
			$text  = __( 'Add images', BWS_BKNG_TEXT_DOMAIN );
			$pop_up_title = __( 'Select images', BWS_BKNG_TEXT_DOMAIN );
			$button_title = __( 'Insert', BWS_BKNG_TEXT_DOMAIN );
		}
		$attr = "data-title=\"{$pop_up_title}\" data-button-title=\"{$button_title}\"";
		$add_button = '<div>' . $bws_bkng->get_link( compact( 'class', 'text', 'attr' ) ) . '</div>';

		/**
		 * Notice, in case Javascript is disabled
		 */
		$noscript_notice =
			'<noscript>
				<div class="error">
					<p>' . __( 'Please enable JavaScript to manage images', BWS_BKNG_TEXT_DOMAIN ) . '.</p>
				</div>
			</noscript>';
		/**
		 * Gallery image micro-template
		 */
		$list_item = '<li class="bkng_post_image" data-image-id="%1$s">%2$s' . $delete_button . '</li>';
		/**
		 * Gallery micro-template
		 */
		$gallery =
			'<div class="' . $wrap_class . '">
				%1$s
				<ul class="bkng_post_gallery"%5$s>%2$s</ul>
				%3$s%4$s
			</div>';

		/*
		 * The list of inages
		 */
		$items = '';
		if ( ! ( empty( $image_ids ) ) ) {
			foreach ( $image_ids as $image_id ) {
				$image = wp_get_attachment_image( $image_id, 'thumbnail' );

				if ( empty( $image ) )
					continue;

				$added[] = $image_id;
				$items  .= sprintf( $list_item, absint( $image_id ), $image );
			}

			$id_list = implode( ',', $added );

			/**
			 * Upadte the gallery data in case if we found any not existed images in it
			 */
			$difference = array_diff( $image_ids, $added );
			if ( ! empty( $added ) && ! empty( $difference ) )
				$this->is_post ? update_post_meta( $this->id, $field, $id_list ) : update_term_meta( $this->id, $field, $id_list );
		}

		/**
		 * Hidden field to keep the list of images IDs, which are binded to the gallery
		 */
		$id    = "{$field}_hidden";
		$class = "bkng_gallery_list_id";
		$name  = "{$this->input_name}[{$field}]";
		$value = $id_list;
		$hidden_input = $bws_bkng->get_hidden_input( compact( 'id', 'class', 'name', 'value' ) );

		return sprintf( $gallery, $noscript_notice, $items, $hidden_input, $add_button, $data_single );

	}

	/**
	 * Include the gallery scripts
	 * @since  0.1
	 * @access public
	 * @static
	 * @param  void
	 * @return void
	 */
	public static function enque_scripts() {
		global $bws_bkng, $pagenow, $plugin_page, $post;

		$dependencies = array( 'jquery' );
		$data         = array( 'is_admin' => $bws_bkng->is_admin() );

		if ( $bws_bkng->is_admin() && ( in_array( $pagenow, array( 'edit-tags.php', 'term.php' ) ) || 'bkng_user_profile' === $plugin_page ) ) {
			wp_enqueue_media();
			wp_print_media_templates();

			$data['set_featured']    = __( 'Set featured image', BWS_BKNG_TEXT_DOMAIN );
			$data['update_featured'] = __( 'Update featured image', BWS_BKNG_TEXT_DOMAIN );
			$dependencies[] = 'jquery-ui-sortable';
		} else {
			if ( $bws_bkng->get_post_type_option( get_post_type( $post ), 'enable_lightbox' ) ) {
				if ( ! wp_script_is( 'bws_fancybox', 'registered' ) ) {
					wp_register_script( 'bws_fancybox', BWS_BKNG_URL . 'assets/fancybox/jquery.fancybox.min.js', array( 'jquery' ) );
				}
				wp_enqueue_style( 'bkng_fancybox_style', BWS_BKNG_URL . 'assets/fancybox/jquery.fancybox.min.css');
				$dependencies[] = 'bws_fancybox';
				$data['fancybox_options'] = apply_filters( 'bws_bkng_fancybox_options', array() );
			}

		}

		wp_enqueue_script(
			'bkng_gallery',
			BWS_BKNG_URL . "js/gallery_handle.js",
			$dependencies,
			false,
			true
		);

		wp_localize_script( 'bkng_gallery', 'bws_bkng_gallery', $data );
	}
}

