<?php
/**
 * Contains plugin options
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

global $bws_post_type,
       $bws_taxonomies,
       $bws_taxonomies_values,
       $bws_metaboxes,
       $bws_attributes_tables,
       $bws_admin_menu_pages,
       $bws_search_form_parameters,
       $bws_search_form_filters,
       $bws_slug_forms,
       $bws_general_subtabs,
       $bws_plugin_settings_subtabs,
       $bws_allow_multiple_relations;

$post_type_array = array(
	'bws_bike' => array(
		'labels'               => array(
			'name'                  => __( 'Bike', 'bike-rental' ),
			'singular_name'         => __( 'Bike', 'bike-rental' ),
			'add_new_item'          => __( 'Add New Bike', 'bike-rental' ),
			'edit_item'             => __( 'Edit Bike', 'bike-rental' ),
			'new_item'              => __( 'New Bike', 'bike-rental' ),
			'view_item'             => __( 'View Bike', 'bike-rental' ),
			'view_items'            => __( 'View Bikes', 'bike-rental' ),
			'search_items'          => __( 'Search Bikes', 'bike-rental' ),
			'not_found'             => __( 'No Bike found', 'bike-rental' ),
			'not_found_in_trash'    => __( 'No Bike found in Trash', 'bike-rental' ),
			'all_items'             => __( 'Bikes', 'bike-rental' ),
			'attributes'            => __( 'Bike Attributes', 'bike-rental' ),
			'insert_into_item'      => __( 'Insert into Bike', 'bike-rental' ),
			'uploaded_to_this_item' => __( 'Uploaded to this Bike', 'bike-rental' ),
			'menu_name'             => __( 'Bikes', 'bike-rental' )
		),
		'supports'             => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'menu_icon'            => 'dashicons-products',
		'public'               => true,
		'show_ui'              => true,
		'show_in_menu'         => true,
		'has_archive'          => true,
		'hierarchical'         => true,
		'capability_type'      => 'post',
		'map_meta_cap'         => true,
		'register_meta_box_cb' => ''
	),
	'bws_extra' => array(
		'labels' => array(
			'name'									=> __( 'Extra', 'bike-rental' ),
			'singular_name'					=> __( 'Extra', 'bike-rental' ),
			'add_new_item'					=> __( 'Add New Extra', 'bike-rental' ),
			'edit_item'							=> __( 'Edit Extra', 'bike-rental' ),
			'new_item'							=> __( 'New Extra', 'bike-rental' ),
			'view_item'							=> __( 'View Extra', 'bike-rental' ),
			'view_items'						=> __( 'View Extras', 'bike-rental' ),
			'search_items'					=> __( 'Search Extras', 'bike-rental' ),
			'not_found'							=> __( 'No Extra found', 'bike-rental' ),
			'not_found_in_trash'		=> __( 'No Extra found in Trash', 'bike-rental' ),
			'all_items'							=> __( 'Extra', 'bike-rental' ),
			'attributes'						=> __( 'Extra Attributes', 'bike-rental' ),
			'insert_into_item'			=> __( 'Insert into Extra', 'bike-rental' ),
			'uploaded_to_this_item'	=> __( 'Uploaded to this Extra', 'bike-rental' ),
			'menu_name'							=> __( 'Extras', 'bike-rental' )
		),
		'supports'        => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'menu_icon'       => 'dashicons-products',
		'public'          => true,
		'show_ui'         => true,
		'show_in_menu'    => true,
		'has_archive'     => true,
		'hierarchical'    => true,
		'capability_type' => 'post',
		'map_meta_cap'    => true,
		'register_meta_box_cb' => ''
	)
);
$bws_post_type   = ! empty( $bws_post_type ) ? array_merge( $bws_post_type, $post_type_array ) : $post_type_array;

$taxonomies_array = array(
	'bws_bike' => array(
		'bike_type'   => array(
			'labels'             => array(
				'name'          => __( 'Types', 'bike-rental' ),
				'singular_name' => __( 'Type', 'bike-rental' ),
				'add_new_item'  => __( 'Add New Type', 'bike-rental' ),
				'edit_item'     => __( 'Edit Type', 'bike-rental' ),
				'new_item'      => __( 'New Type', 'bike-rental' ),
				'view_item'     => __( 'View Type', 'bike-rental' ),
				'search_items'  => __( 'Search Types', 'bike-rental' ),
				'not_found'     => __( 'No Types found', 'bike-rental' ),
				'menu_name'     => __( 'Types', 'bike-rental' )
			),
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_ui'            => true,
			'show_in_quick_edit' => false,
			'show_tagcloud'      => true,
			'show_admin_column'  => true,
			'query_var'          => true,
			'hierarchical'       => true
		)
	),
	'bws_extra' => array(
		'extra_type' => array(
			'labels'             => array(
				'name'          => __( 'Types', 'bike-rental' ),
				'singular_name' => __( 'Type', 'bike-rental' ),
				'add_new_item'  => __( 'Add New Type', 'bike-rental' ),
				'edit_item'     => __( 'Edit Type', 'bike-rental' ),
				'new_item'      => __( 'New Type', 'bike-rental' ),
				'view_item'     => __( 'View Type', 'bike-rental' ),
				'search_items'  => __( 'Search Types', 'bike-rental' ),
				'not_found'     => __( 'No Types found', 'bike-rental' ),
				'menu_name'     => __( 'Types', 'bike-rental' )
			),
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_ui'            => true,
			'show_in_quick_edit' => false,
			'show_tagcloud'      => true,
			'show_admin_column'  => true,
			'query_var'          => true,
			'hierarchical'       => true
		)
	)
);
$bws_taxonomies   = ! empty( $bws_taxonomies ) ? array_merge( $bws_taxonomies, $taxonomies_array ) : $taxonomies_array;

$taxonomies_values_array = array();
$bws_taxonomies_values   = ! empty( $bws_taxonomies_values ) ? array_merge( $bws_taxonomies_values, $taxonomies_values_array ) : $taxonomies_values_array;

$metaboxes_array = array(
	'bws_bike' => array(
		'metabox'        => array( 'BWS_BKNG_Post_Metabox', 'add_meta_boxes' ),
		'args'           => array( 'preferences', 'gallery' ),
		'general_tab'    => array( 'statuses', 'quantity_available', 'quantity', 'sku' ),
		'general_labels' => array(
			'statuses'           => array(
				'name'           => __( 'Bike Status', 'bike-rental' ),
				'description'    => '',
				'type_id'        => 5,
				'visible_status' => 0
			),
			'quantity_available' => array(
				'name'           => __( 'Ability to Choose Quantity', 'bike-rental' ),
				'description'    => '',
				'type_id'        => 3,
				'visible_status' => 0
			),
			'quantity'           => array(
				'name'           => __( 'Quantity', 'bike-rental' ),
				'description'    => '',
				'type_id'        => 9,
				'visible_status' => 0
			),
			'sku'                => array(
				'name'           => __( 'SKU', 'bike-rental' ),
				'description'    => '',
				'type_id'        => 1,
				'visible_status' => 0
			)
		),
		'price_tab'    => array( 'price_on_request', 'price', 'on_price_by_days', 'price_by_days', 'price_by_seasons' ),
		'price_labels' => array(
			'price_on_request'   => array(
				'name'           => __( 'Price on Request', 'bike-rental' ),
				'description'    => '',
				'type_id'        => 3,
				'visible_status' => 0
			),
			'price'              => array(
				'name'           => __( 'Price', 'bike-rental' ),
				'description'    => '',
				'type_id'        => 9,
				'visible_status' => 0
			),
			'on_price_by_days'   => array(
				'name'           => __( 'Price by days', 'bike-rental' ),
				'description'    => '',
				'type_id'        => 3,
				'visible_status' => 0
			),
			'price_by_days'      => array(
				'name'           => array(
					'day_from' 	=> __( 'Day From', 'bike-rental' ),
					'day_to' 	=> __( 'Day To', 'bike-rental' ),
					'price' 	=> __( 'Price', 'bike-rental' ),
				),
				'description'    => '',
				'type_id'        => 11, /* array of arrays (serialized) */
				'visible_status' => 0
			),
			'price_by_seasons'   => array(
				'name'           => array(
					'winter' 	=> __( 'Price in Winter Season', 'bike-rental' ),
					'spring' 	=> __( 'Price in Spring Season', 'bike-rental' ),
					'summer' 	=> __( 'Price in Summer Season', 'bike-rental' ),
					'autumn' 	=> __( 'Price in Autumn Season', 'bike-rental' ),
				),
				'description'    => '',
				'type_id'        => 10, /* array (serialized) */
				'visible_status' => 0
			),
		),
	),
	'bws_extra' => array(
		'metabox'        => array( 'BWS_BKNG_Post_Metabox', 'add_meta_boxes' ),
		'args'           => array( 'preferences', 'gallery' ),
		'general_tab'    => array( 'statuses', 'price_on_request', 'price', 'quantity_available', 'quantity', 'sku' ),
		'general_labels' => array(
			'statuses'           => array(
				'name'           => __( 'Extra Status', 'bike-rental' ),
				'description'    => '',
				'type_id'        => 5,
				'visible_status' => 0
			),
			'price_on_request'   => array(
				'name'           => __( 'Price on Request', 'bike-rental' ),
				'description'    => '',
				'type_id'        => 3,
				'visible_status' => 0
			),
			'price'              => array(
				'name'           => __( 'Price', 'bike-rental' ),
				'description'    => '',
				'type_id'        => 9,
				'visible_status' => 0
			),
			'quantity_available' => array(
				'name'           => __( 'Ability to Choose Quantity', 'bike-rental' ),
				'description'    => '',
				'type_id'        => 3,
				'visible_status' => 0
			),
			'quantity'           => array(
				'name'           => __( 'Quantity', 'bike-rental' ),
				'description'    => '',
				'type_id'        => 9,
				'visible_status' => 0
			),
			'sku'                => array(
				'name'           => __( 'SKU', 'bike-rental' ),
				'description'    => '',
				'type_id'        => 1,
				'visible_status' => 0
			)
		)
	)
);

$bws_metaboxes = ! empty( $bws_metaboxes ) ? array_merge( $bws_metaboxes, $metaboxes_array ) : $metaboxes_array;

$attributes_tables_array = array( 'bws_bike', 'bws_extra' );
$bws_attributes_tables   = ! empty( $bws_attributes_tables ) ? array_merge( $bws_attributes_tables, $attributes_tables_array ) : $attributes_tables_array;

$admin_menu_pages_array = array(
	'bws_bike' => array(
		'attributes_page'       => array(
			'slug'        => '%PREFIX%_%POST_TYPE%_attributes',
			'title'       => __( 'Attributes', 'bike-rental' ),
			'callback'    => 'display_page',
			'capability'  => 'manage_options',
			'parent_page' => 'edit.php?post_type=%POST_TYPE%'
		),
		'locations_page' => array(
			'slug'       => '%PREFIX%_%POST_TYPE%_locations',
			'title'      => __( 'Locations', 'bike-rental' ),
			'callback'   => 'display_page',
			'capability' => 'manage_options',
			'parent_page' => 'edit.php?post_type=%POST_TYPE%'
		),
		'orders_page' => array(
			'slug'				=> '%PREFIX%_%POST_TYPE%_orders',
			'title'				=> __( 'Orders', 'bike-rental' ),
			'callback'		=> 'display_table',
			'capability'	=> 'manage_options',
			'parent_page' => 'edit.php?post_type=%POST_TYPE%',
			'load'				=> array(
				'hook'			=> 'load-%PAGE%',
				'function'	=> array( 'BWS_BKNG_Admin', 'add_screen_options_tabs' )
			)
		),
		'user_page' => array(
			'slug'       => 'bkng_user_profile',
			'title'      => __( 'Your profile', 'bike-rental' ),
			'callback'   => 'display',
			'capability' => 'read',
			'parent_page' => 'edit.php?post_type=%POST_TYPE%',
		),
		'general_settings_page' => array(
			'slug'        => 'bkng_general_settings',
			'title'       => __( 'General Settings', 'bike-rental' ),
			'callback'    => 'display',
			'capability'  => 'manage_options',
			'parent_page' => 'edit.php?post_type=%POST_TYPE%'
		),
//		'settings_page'         => array(
//			'slug'        => '%PREFIX%_%POST_TYPE%_settings',
//			'title'       => __( 'Settings', 'bike-rental' ),
//			'callback'    => 'display',
//			'capability'  => 'manage_options',
//			'parent_page' => 'edit.php?post_type=%POST_TYPE%'
//		),
		'bws_panel'             => array(
			'slug'        => '%PREFIX%-bws-panel',
			'title'       => 'BWS Panel',
			'callback'    => 'bws_add_menu_render',
			'capability'  => 'manage_options',
			'parent_page' => 'edit.php?post_type=%POST_TYPE%',
			'load'        => array(
				'hook'     => 'load-%PAGE%',
				'function' => array( 'BWS_BKNG_Admin', 'add_help_tabs' )
			)
		)
	),
	'bws_extra' => array(
		'general_settings_page' => array(
			'slug'        => 'bkng_general_settings',
			'title'       => __( 'General Settings', 'bike-rental' ),
			'callback'    => 'display',
			'capability'  => 'manage_options',
			'parent_page' => 'edit.php?post_type=%POST_TYPE%'
		),
//		'settings_page'         => array(
//			'slug'        => '%PREFIX%_%POST_TYPE%_settings',
//			'title'       => __( 'Settings', 'bike-rental' ),
//			'callback'    => 'display',
//			'capability'  => 'manage_options',
//			'parent_page' => 'edit.php?post_type=%POST_TYPE%'
//		),
		'bws_panel'             => array(
			'slug'        => '%PREFIX%-bws-panel',
			'title'       => 'BWS Panel',
			'callback'    => 'bws_add_menu_render',
			'capability'  => 'manage_options',
			'parent_page' => 'edit.php?post_type=%POST_TYPE%',
			'load'        => array(
				'hook'     => 'load-%PAGE%',
				'function' => array( 'BWS_BKNG_Admin', 'add_help_tabs' )
			)
		)
	)
);
$bws_admin_menu_pages   = ! empty( $bws_admin_menu_pages ) ? array_merge( $bws_admin_menu_pages, $admin_menu_pages_array ) : $admin_menu_pages_array;
$search_form_parameters = array(
	'bws_bike' => array(
		'location' => array(
			'format'         => 'text',
			'label'          => __( 'Your Destination', 'bike-rental' ),
			'placeholder'    => __( 'Enter city, region or district', 'bike-rental' ),
			'label_position' => 'none'
		),
		'from'     => array(
			'format'         => 'data',
			'label'          => __( 'Check In', 'bike-rental' ),
			'placeholder'    => ' ',
			'label_position' => 'none'
		),
		'till'     => array(
			'format'         => 'data',
			'label'          => __( 'Check Out', 'bike-rental' ),
			'placeholder'    => ' ',
			'label_position' => 'none'
		),
	)
);
$bws_search_form_parameters = ! empty( $bws_search_form_parameters ) ? array_merge( $bws_search_form_parameters, $search_form_parameters ) : $search_form_parameters;
$search_form_filters     = array(
	'bws_bike' => array(
		'price'  => __( 'Price', 'bike-rental' ),
		'rating' => __( 'User Rating', 'bike-rental' ),
		'extras' => __( 'Extras', 'bike-rental' ),
	)
);
$bws_search_form_filters = ! empty( $bws_search_form_filters ) ? array_merge( $bws_search_form_filters, $search_form_filters ) : $search_form_filters;
$slug_forms = array(
	'bws_bike' => array(
		'slug' => 'bike-rental',
		'pure_slug' => 'bkrntl',
	),
	'bws_extra' => array(
		'slug' => 'bike-rental',
		'pure_slug' => 'bkrntl',
	),
);
$bws_slug_forms =  ! empty( $bws_slug_forms ) ? array_merge( $bws_slug_forms, $slug_forms ) : $slug_forms;
$general_subtabs = array(
	'bike_rental' => array(
		'label' => __( 'Bike Rental Settings', BWS_BKNG_TEXT_DOMAIN ),
	),
);
$bws_general_subtabs = ! empty( $bws_general_subtabs ) ? array_merge( $bws_general_subtabs, $general_subtabs ) : $general_subtabs;
$plugin_settings_subtabs = array(
	'bike_rental' => array(
		'label' => __( 'Bikes', BWS_BKNG_TEXT_DOMAIN ),
	),
	'extras' => array(
		'label' => __( 'Extras', BWS_BKNG_TEXT_DOMAIN ),
	),
);
$bws_plugin_settings_subtabs = ! empty( $bws_plugin_settings_subtabs ) ? array_merge( $bws_plugin_settings_subtabs, $plugin_settings_subtabs ) : $plugin_settings_subtabs;

$allow_multiple_relations = array(
    'bws_extra' => true,
);
$bws_allow_multiple_relations = ! empty( $bws_allow_multiple_relations ) ? array_merge( $bws_allow_multiple_relations, $allow_multiple_relations ) : $allow_multiple_relations;

if ( ! function_exists( 'bws_bkng_get_plugin_data' ) ) {
	function bws_bkng_get_plugin_data( $data ) {
		$plugin_data = array(
			'bike-rental/bike-rental.php' => array(
				'name'				=> 'Bike Rental',
				'slug'				=> 'bike-rental',
				'show_in'			=> array(
					'Checkout page'		=> 'checkout',
					'Registration form'	=> 'registration',
				),
				'actions'	=> array(
					'bws_bkng_checkout_bike-rental',
					'bws_bkng_registration_bike-rental',
				),
			)
		);

		if ( ! empty( $data ) ) {
			$plugin_data = array_merge( $plugin_data, $data );
		}

		return $plugin_data;
	}
}

add_filter( 'bws_bkng_prflxtrflds_get_data', 'bws_bkng_get_plugin_data' );
