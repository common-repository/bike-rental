<?php
/**
 * Loads Booking classes
 * @since   Booking v0.1
 * @package Booking
 * @author  BestWebSoft
 * @param   string       $class     Class name
 * @return  void
 */
if ( ! function_exists( 'bws_bkng_core_autoload' ) ) {
	function bws_bkng_core_autoload( $class ) {
		$sep  = DIRECTORY_SEPARATOR;
		$file = $sep . strtolower( $class ) . '.php';
        if ( ! strpos( $file, 'bws_bkng' ) )
			return;

		$path    = dirname( __FILE__ ) . "{$sep}classes{$sep}";
		$folders = array( 'general', 'helpers', 'widgets', 'front-end', 'admin', 'abstract' );
        foreach( $folders as $folder ) {
			if ( file_exists( "{$path}{$folder}{$file}" ) ) {
				require_once( "{$path}{$folder}{$file}" );
				return;
			}
		}

		if ( strpos( $file, 'settings' ) && file_exists( "{$path}admin{$sep}settings{$file}" ) ) {
			require_once( "{$path}admin{$sep}settings{$file}" );
		}
        if ( strpos( $file, 'profile' ) && file_exists( "{$path}admin{$sep}profile{$file}" ) ) {
			require_once( "{$path}admin{$sep}profile{$file}" );
		}

		require_once( dirname( __FILE__ ) . "/classes/front-end/bws_bkng_paypal.php");
	}

	spl_autoload_register( 'bws_bkng_core_autoload' );
}