<?php
/**
 * Displays the not-found-message
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

do_action( 'bws_bkng_after_content_none' ); ?>

<div id="bws-bkng-none"><?php _e( "Sorry, we couldn't find any results matching this search", BWS_BKNG_TEXT_DOMAIN ); ?>.</div>

<?php do_action( 'bws_bkng_after_content_none' ); ?>

