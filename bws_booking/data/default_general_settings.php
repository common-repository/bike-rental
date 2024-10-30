<?php
/**
 * NOT USED ANYMORE
 * @deprecated
 * Contains default plugin options
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( ! function_exists( 'get_user_by' ) )
	require_once ABSPATH . 'wp-includes/pluggable.php';

global $bws_bkng;
$info        = $bws_bkng->get_plugin_info();
$admin_email = get_option( 'admin_email' );

$defaults_general = array(
/***********************************
######## Not editable options ######
***********************************/

	'display_settings_notice' => 1,
	'suggest_feature_banner'  => 1,
	'plugin_option_version'   => ( $bws_bkng->is_pro ? "pro-{$info['Version']}" : $info['Version'] ),
	'plugin_db_version'       => $bws_bkng->get_db_version(),

/*************************************
########## Editable options ##########
*************************************/

	/***
	 *** The "General" tab settings
	 ***/
	'currency'                   => '&#36;',
	'currency_code'              => 'USD',  /* Was introduced for further interaction with payment processors. For now it is not used */
	'currency_position'          => 'left', /* Variations: 'right', 'left' */
	'price_type'                 => 'basic', /* Variations: 'basic', 'seasons' */
	'number_decimals'            => 2,
	'number_thousand_separator'  => '&nbsp;',
	'number_decimal_separator'   => '.',
	'google_map_key'             => '',
	'google_map_auto_detect'     => true,
	'google_map_language'        => 'en',
	'google_map_region'          => '',
	'google_map_default_lat'     => 40.7127837,
	'google_map_default_lng'     => -74.00594130000002,
	'google_map_default_address' => 'New York',

	/***
	 *** The "Cart" tab settings
	 ***/
	'cart_page'          => 0,
	'keep_goods_in_cart' => 500,

	/***
	 *** The "Checkout" tab settings
	 ***/
	//'checkout_page'         => 0,
	//'thank_you_page'        => 0,
	'checkout_registration' => 'yes', /* Variations: 'yes', 'no', 'user' */

	/***
	 *** The "Paypal" tab settings
	 ***/
	'paypal'         		=> 0,
	'paypal_clientid'  	=> "",
	'paypal_secret' 		=> "",

	/***
	 ***  Free version post limit by default
	 ***/
	'cflag'	=> '0A',
	'eflag'	=> '03',

	/***
	 *** The "Accounts" tab settings
	 ***/
	// 'user_account_page'    => 0,
	// 'account_registration' => true,
	// 'account_endpoints' => array(
	// 	'history'       => 'history',
	// 	'orders'        => 'orders',
	// 	'view_order'    => 'view_order',
	// 	'settings'      => 'settings',
	// 	'favorite'      => 'favorite',
	// 	'lost_password' => 'lost-password',
	// 	'logout'        => 'logout'
	// ),

	/***
	 *** The "Agents" tab settings
	 ***/
	// 'agents_page' => 0,

	/***
	 *** The "Agencies" tab settings
	 ***/
	/*'enable_agencies'          => true,
	'agencies_page'            => 0,
	'agencies_additional_meta' => array(
		'location'       => true,
		'phone'          => true,
		'working_hours'  => false,
		'featured_image' => true,
		'image_gallery'  => false
	),*/

	/***
	 *** The "Notifications" tab settings
	 ***/
	//'agent_sender_email'          => '',
	//'agent_recipient_emails'      => array(),
	//'additional_recipient_emails' => array(),
	//'from_value'                  => 'agent', /*  Variations: 'agent', 'custom' */
	//'from_name'                   => get_bloginfo( 'name' ),
	//'from_email'                  => $admin_email
);

$pages = array(
	//'products_page'     => array( 'slug' => 'products',  'title' => __( 'Products', BWS_BKNG_TEXT_DOMAIN ) ),
	//'checkout_page'     => array( 'slug' => 'checkout',  'title' => __( 'Checkout', BWS_BKNG_TEXT_DOMAIN ) ),
	//'thank_you_page'    => array( 'slug' => 'thank_you', 'title' => __( 'Thank you', BWS_BKNG_TEXT_DOMAIN ) ),
	//'agencies_page'     => array( 'slug' => 'agencies',  'title' => __( 'Agencies', BWS_BKNG_TEXT_DOMAIN ) ),
	'cart_page'         => array( 'slug' => 'cart',    'title' => __( 'Cart', BWS_BKNG_TEXT_DOMAIN ) ),
	// 'failure_page'      => array( 'slug' => 'failure', 'title' => __( 'Fail', BWS_BKNG_TEXT_DOMAIN ) ),
	// 'user_account_page' => array( 'slug' => 'account', 'title' => __( 'Account', BWS_BKNG_TEXT_DOMAIN ) ),
	// 'agents_page'       => array( 'slug' => 'agents',  'title' => __( 'Agents', BWS_BKNG_TEXT_DOMAIN ) ),
);

/**
 * Check and create front end pages
 */
foreach( $pages as $page_type => $data ) {
	$page = get_page_by_path( $data['slug'] );
	$args = array(
		'post_type'      => 'page',
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
		'post_status'    => 'publish',
		'post_name'      => $data['slug'],
		'post_title'     => $data['title'],
		'post_content'   => '',
	);
	$defaults_general[ $page_type ] = empty( $page->ID ) ? absint( wp_insert_post( $args ) ) : $page->ID ;
}

/*
 * Set the email addresses of the sender and the recipients of messages intended for agents
 *
 * If the user with admin email exists:
 * - then he will act as a sender of messages to customers and the recipient of service messages;
 * - In the field "From" in emails to customers will be his name.
 */
/*if ( get_user_by( 'email', $admin_email ) ) {
	$defaults_general['agent_sender_email'] = $admin_email;
	array_push( $defaults_general['agent_recipient_emails'], $admin_email );*/
/*
 * otherwise:
 * - the site email will be written as an email-recipient of service messages;
 * - In the field "From" in emails to customers will be the site title.
 */
/*} else {
	$defaults_general['from_value'] = 'custom';
	array_push( $defaults_general['additional_recipient_emails'], $admin_email );
}*/

return $defaults_general;
