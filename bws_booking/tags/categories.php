<?php /**
 * Contains the list of functions are used to handle product categories
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

/**
 * Fecth the list of product categories
 * @since    0.1
 * @param    void
 * @return   array/false      Array, the list of WP_Term objects - if all is OK, false otherise
 */
if ( ! function_exists( 'bws_bkng_get_categories' ) ) {
	function bws_bkng_get_categories() {
		global $bws_bkng;
		$categories = $bws_bkng->get_terms(
			BWS_BKNG_CATEGORIES,
			array(
				'hide_empty' => apply_filters( 'bws_bkng_hide_empty_categories', true ),
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key'     => 'bkng_exclude_from_search',
						'value'   => 1,
						'compare' => '!='
					),
					array(
						'key'     => 'bkng_exclude_from_search',
						'value'   => '',
						'compare' => 'NOT EXISTS'
					)
				)
			)
		);
		return is_array( $categories ) ? $categories : array();
	}
}

/**
 * Outputs additional HTML attributes for each product category
 * @since    0.1
 * @param    string      $slug       Category slug
 * @return   void
 */
if ( ! function_exists( 'bws_bkng_category_classes' ) ) {
	function bws_bkng_category_classes( $slug ) {

		$attributes = 'class="%1$s"';

		$classes = array( 'bws_bkng_category', "bws_bkng_category_" . esc_attr( $slug ) );
		$classes = join( ' ', apply_filters( 'bws_bkng_category_class', $classes ) );

		printf( $attributes, $classes );
	}
}

/**
 * Outputs featured image for category
 * @since    0.1
 * @param    int   $cat_id     Category ID
 * @return   void
 */
if ( ! function_exists( 'bws_bkng_category_thumbnail' ) ) {
	function bws_bkng_category_thumbnail( $category ) {

		if ( ! $category instanceof WP_Term ) {
			_e( 'No image', BWS_BKNG_TEXT_DOMAIN );
			return;
		}

		$cat_id = absint( $category->term_id );

		if ( empty( $cat_id ) ) {
			echo get_default_image( $category->name );
			return;
		}

		$thumb_id   = get_term_meta( $cat_id, BWS_BKNG_POST . '_featured_image', true );
		$attachment = wp_get_attachment_image_src( $thumb_id, 'bkng_catalog_' . get_post_type() . '_image' );

		if ( empty( $attachment[0] ) ) {
			echo get_default_image( $category->name );
			return;
		}

		echo '<img src="' . esc_url( $attachment[0] ) . '" />';
	}
}

if ( ! function_exists( 'bws_bkng_categories_list' ) ) {
	function bws_bkng_categories_list( $categories = '' ) {
		global $bws_bkng;

		if ( empty( $categories ) ) {
			$categories = bws_bkng_get_categories();
			if ( empty( $categories ) )
				return;
		}

		$query         = bws_bkng_get_query();
		$taxonomy_data = get_taxonomy( BWS_BKNG_CATEGORIES );

		$data = array(
			'label' => $taxonomy_data->label,
			'type'  => 'select',
			'value' => empty( $query[ BWS_BKNG_CATEGORIES ] ) ? '' : $query[ BWS_BKNG_CATEGORIES ],
			'list'  => array(),
			'placeholder' => apply_filters( 'bws_bkng_categories_list_placeholder', __( 'Select Category', BWS_BKNG_TEXT_DOMAIN ) )
		);

		foreach( $categories as $category )
			$data['list'][ $category->slug ] = $category->name;

		bws_bkng_items_list( BWS_BKNG_CATEGORIES, $data );
	}
}