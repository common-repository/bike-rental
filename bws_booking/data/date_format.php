<?php
/**
 * Contains the list of date format
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

return array(
	'Y/m/d'  => date( 'Y/m/d' ),
	'm/d/Y'  => date( 'm/d/Y' ),
	'Y-m-d'  => date( 'Y-m-d' ),
	'm-d-Y'  => date( 'm-d-Y' ),
	'd_M_Y'  => date( 'j M Y' ),
	'M_d_Y'  => date( 'M j Y' )
);