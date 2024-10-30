<?php
/**
 * Helper for settings page
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Categories_Metabox' ) )
	return;

class BWS_BKNG_Categories_Metabox extends BWS_BKNG_Term_Metabox {

	/**
	 * Class constructor
	 * @since  0.1
	 * @access public
	 * @param   void
	 * @return  void
	 */
	public function __construct() {

		parent::__construct( BWS_BKNG_CATEGORIES, __CLASS__ );

		/* check and set the default taxonomy term */
		new BWS_BKNG_Default_Term( BWS_BKNG_CATEGORIES );

		if ( ! $this->get_terms() )
			wp_insert_term( __( 'Uncategorized' ), BWS_BKNG_CATEGORIES );

		add_action( 'admin_footer', array( 'BWS_BKNG_Image_Gallery', 'enque_scripts' ) );
	}

	/**
	 * Fetch the list of registered categories
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return array|false       The list of term taxonomies data. False otherwise.
	 */
	public function get_terms() {
		global $bws_bkng;
		$terms = $bws_bkng->get_terms( BWS_BKNG_CATEGORIES, array( 'hide_empty' => false ) );
		return is_wp_error( $terms ) || empty( $terms ) ? false : $terms;
	}

	/**
	 * Fetch the list of metaboxes to be displayed on the edit category page
	 * @see    BWS_BKNG_Term_Metabox::get_items()
	 * @since  0.1
	 * @access public
	 * @param    object|string $tag              An instance of the WP_TERM class the taxonomy slug otherwise
	 * @param    string        $tax_slug         The term taxonomy slug
	 * @param    boolean       $is_edit_page     Whether the metabox will be displayed on the single term edit page or on the add-new-term page
	 * @return array
	 */
	public function get_items( $tag, $tax_slug = null, $is_edit_page ) {
		global $bws_bkng;

		/**
		 * Rent interval metabox content
		 */
		$meta      = $is_edit_page ? get_term_meta( $tag->term_id, 'bkng_rental_interval', true ) : '';
		$intervals = $bws_bkng->get_rent_interval();
		$name      = "bkng_rental_interval";
		$selected  = ! empty( $meta ) && in_array( $meta, array_keys( $intervals ) ) ? $meta : 'day';
		$options   = array();
		$id        = $is_edit_page ? $tag->term_id : 0;
		$gallery   = new BWS_BKNG_Image_Gallery( $id, "bkng_category_gallery", false );

		foreach ( $intervals as $slug => $data )
			$options[ $slug ] = $data[0];

		$rent_interval_content = $bws_bkng->get_select( compact( 'name', 'options', 'selected' ) );

		/**
		 * Exclude From Search Metabox content
		 */
		$name = "bkng_exclude_from_search";
		$attr = $is_edit_page && get_term_meta( $tag->term_id, 'bkng_exclude_from_search', true ) ? ' checked="checked"' : '';
		$exclude_content = $bws_bkng->get_checkbox( compact( 'name', 'attr' ) );

		return array(
			'featured_image' => array(
				'label'   => __( 'Featured Image', BWS_BKNG_TEXT_DOMAIN ),
				'content' => $gallery->get_content( 'featured_image' )
			),
			'rent_interval' => array(
				'label'   => __( 'Default Rent Interval', BWS_BKNG_TEXT_DOMAIN ),
				'content' => $rent_interval_content
			),
			'exclude' => array(
				'label'   => __( 'Exclude From Search', BWS_BKNG_TEXT_DOMAIN ),
				'content' => $exclude_content
			)
		);
	}

	/**
	 * Save the category data
	 * @see    BWS_BKNG_Term_Metabox::save_term_data()
	 * @since  0.1
	 * @access public
	 * @param  int        $term_id      The term ID
	 * @param  int        $tt_id        The term taxonomy ID
	 * @param  string     $tax_slug     The term taxonomy slug
	 * @return void
	 */
	public function save_term_data( $term_id, $tt_id, $tax_slug = '' ) {
		global $bws_bkng;

		$rent_interval =
				empty( $_POST['bkng_rental_interval'] ) ||
				! in_array( $_POST['bkng_rental_interval'], $bws_bkng->get_rent_interval( '', 'keys' ) )
			?
				'day'
			:
				esc_sql( sanitize_text_field( stripslashes( $_POST['bkng_rental_interval'] ) ) );

		update_term_meta( $term_id, "bkng_rental_interval", $rent_interval );
		update_term_meta( $term_id, "bkng_exclude_from_search", ! empty( $_POST['bkng_exclude_from_search'] ) );

		$gallery = new BWS_BKNG_Image_Gallery( $term_id, "bkng_category_gallery", false );
		$gallery->save_images();
	}

}