<?php
/**
 * @uses     To keep data in cookie
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Local_Session' ) )
	return;

class BWS_BKNG_Local_Session extends BWS_BKNG_Session {

	/**
	 * Contains cookie name
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    string
	 */
	private $cookie_name = 'bws_bkng_session';

	/**
	 * Fetch the class instance
	 * @since    0.1
	 * @access   public
	 * @static
	 * @param    void
	 * @return   object           An instance of the current class
	 */
	public static function get_instance( $is_forced_cookie = false ) {

		$storage = $is_forced_cookie ? 'cookie_handler' : 'storage_handler';

		if ( ! self::$$storage instanceof self )
			self::$$storage = new self( $is_forced_cookie );

		return self::$$storage;
	}

	/**
	 * Add cookie data
	 * @since    0.1
	 * @access   public
	 * @param    string    $key    The data type
	 * @param    string    $data   Data which need to add to the storage
	 * @return   boolean
	 */
	protected function add_to_storage( $key, $data ) {
		$expire = $this->is_forced_cookie ? 0 : $this->get_expire_time( $key );
		return $this->set_cookie( $key, $data, $expire );
	}

	/**
	 * Delete cookie
	 * @since    0.1
	 * @access   public
	 * @param    string    $key
	 * @return   boolean
	 */
	protected function remove_from_storage( $key ) {
		return $this->set_cookie( $key, null, time() - HOUR_IN_SECONDS );
	}

	/**
	 * Fetch data from cookie
	 * @since    0.1
	 * @access   public
	 * @param    string    $key The data type
	 * @return   boolean
	 */
	protected function get_from_storage( $key ) {
		return empty( $_COOKIE[ "{$this->cookie_name}-{$key}" ] ) ? null : $_COOKIE[ "{$this->cookie_name}-{$key}" ];
	}

	/**
	 * Update cookie data
	 * @since    0.1
	 * @access   public
	 * @param    string    $key    The data type
	 * @param    string    $data   Data which need to add to the storage
	 * @return   boolean
	 */
	protected function update_storage( $key, $data ) {
		$this->remove_from_storage( $key );
		return $this->add_to_storage( $key, $data );
	}


	private function __construct( $is_forced_cookie ) {

		if ( ! $is_forced_cookie )
			return;

		$this->cookie_name = "{$this->cookie_name}_temp";
		$this->is_forced_cookie = true;
	}

	/**
	 * Set cookie
	 * @since    0.1
	 * @access   public
	 * @param    string    $key      The data type
	 * @param    string    $data     Data which need to add to the storage
	 * @param    int       $expire   Cookie expire time in unix-timestamp format
	 * @return   boolean
	 */
	private function set_cookie( $key, $data, $expire ) {
		return setcookie( "{$this->cookie_name}-{$key}", $data, $expire, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
	}

	/**
	 * Some magic to avoid the creation of several instances of this class
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   void
	 */
	private function __clone()  {}
	private function __sleep()  {}
	private function __wakeup() {}
}