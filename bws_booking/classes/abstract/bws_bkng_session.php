<?php
/**
 * @uses     To manage the data in the session storage (cookie or database)
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Session' ) )
	return;

abstract class BWS_BKNG_Session {

	/**
	 * Whether to force use cookie storage
	 * @since  0.1
	 * @access protected
	 * @static
	 * @var    boolean
	 */
	protected $is_forced_cookie = false;

	/**
	 * Contains an instance of the current class
	 * @since  0.1
	 * @access protected
	 * @static
	 * @var    object
	 */
	protected static $storage_handler;

	/**
	 * Contains an instance of the current class
	 * @since  0.1
	 * @access protected
	 * @static
	 * @var    object
	 */
	protected static $cookie_handler;

	/**
	 * Fetch the class instance
	 * @since    0.1
	 * @access   public
	 * @static
	 * @param    boolean   Whether to force init the cookie with zero lifetime ( they will be terminated when the browser is closed )
	 * @return   object    An instance of the current class
	 */
	public static function get_instance( $is_forced_cookie = false ) {

		if ( $is_forced_cookie ) {

			if ( ! self::$cookie_handler instanceof BWS_BKNG_Local_Session )
				self::$cookie_handler = BWS_BKNG_Local_Session::get_instance( true );

			return self::$cookie_handler;
		}

		if ( ! is_null( self::$storage_handler ) )
			return self::$storage_handler;

		$cookie_handler = BWS_BKNG_Local_Session::get_instance( false );

		if ( is_user_logged_in() ) {

			self::$storage_handler = BWS_BKNG_DB_Session::get_instance();

			/* re-save data from cookie to database */
			foreach( (array)self::get_keys() as $key ) {
				
				$cookie_data = json_decode( stripslashes( $cookie_handler->get_from_storage( $key ) ), true );

				if ( ! $cookie_data )
					continue;

				$db_data = self::$storage_handler->get( $key );

				if ( empty( $db_data ) ) {
					self::$storage_handler->add( $key, $cookie_data );
				} else {
					/**
					 * This hook is should to be used in the functional entities that keep their data in the session storage
					 * {eg @see the BWS_BKNG_Cart class definition}
					 */
					$db_data = apply_filters( 'bws_bkng_merge_storage', $db_data, $cookie_data, $key );
					self::$storage_handler->update( $key, $db_data );
				}

				$cookie_handler->remove_from_storage( $key );
			}
		} else {
			self::$storage_handler = $cookie_handler;
		}

		return self::$storage_handler;
	}

	/**
	 * Adds data to the current storage
	 * @abstract
	 * @since    0.1
	 * @access   protected
	 * @param    string    $key    The data type
	 * @param    mixed     $data   Data which need to add to the storage
	 */
	abstract protected function add_to_storage( $key, $data );

	/**
	 * Fetch data from the current storage
	 * @abstract
	 * @since    0.1
	 * @access   protected
	 * @param    string    $key    The data type
	 */
	abstract protected function get_from_storage( $key );

	/**
	 * Updates data from the current storage
	 * @abstract
	 * @since    0.1
	 * @access   protected
	 * @param    string    $key    The data type
	 * @param    mixed     $data   Data which need to add to the storage
	 */
	abstract protected function update_storage( $key, $data );

	/**
	 * Remove data from the current storage
	 * @abstract
	 * @since    0.1
	 * @access   protected
	 * @param    string    $key    The data type
	 */
	abstract protected function remove_from_storage( $key );

	/**
	 * Adds data to the session storage
	 * @since    0.1
	 * @access   public
	 * @param    string    $key    The data type
	 * @param    mixed     $data   Data which need to add to the storage
	 * @return   boolean           Whether data were added successfully
	 */
	public function add( $key, $data ) {

		$key = sanitize_key( $key );

		if ( empty( $key ) )
			return null;

		$storage = $this->is_forced_cookie ? 'cookie_handler' : 'storage_handler';

		return self::$$storage->add_to_storage( $key, $this->prepare( $key, $data ) );
	}

	/**
	 * Fetch data from the session storage
	 * @since    0.1
	 * @access   public
	 * @param    string    $key    The data type
	 * @return   mixed             The data
	 */
	public function get( $key ) {

		$key = sanitize_key( $key );

		if ( empty( $key ) )
			return null;

		$storage = $this->is_forced_cookie ? 'cookie_handler' : 'storage_handler';

		return json_decode( stripslashes( self::$$storage->get_from_storage( $key ) ), true );
	}

	/**
	 * Updates data in the session storage
	 * @since    0.1
	 * @access   public
	 * @param    string    $key    The data type
	 * @param    mixed     $data   Data which need to update
	 * @return   boolean           Whether data were updated successfully
	 */
	public function update( $key, $data ) {

		$key = sanitize_key( $key );

		if ( empty( $key ) )
			return null;

		$storage = $this->is_forced_cookie ? 'cookie_handler' : 'storage_handler';
		return self::$$storage->update_storage( $key, $this->prepare( $key, $data ) );
	}

	/**
	 * Removes data from the session storage
	 * @since    0.1
	 * @access   public
	 * @param    string    $key    The data type
	 * @return   boolean           Whether data were removed successfully
	 */
	public function remove( $key ) {

		$key = sanitize_key( $key );

		if ( empty( $key ) )
			return null;

		$storage = $this->is_forced_cookie ? 'cookie_handler' : 'storage_handler';

		return self::$$storage->remove_from_storage( $key );
	}

	/**
	 * Fetch the storage expire time
	 * @since    0.1
	 * @access   protected
	 * @param    string    $key    The data type
	 * @return   int               The time in seconds in the Unix timestamp format
	 */
	protected function get_expire_time( $key ) {
		global $bws_bkng;

		switch( $key ) {
			case 'cart':
				$days = $bws_bkng->get_option( 'keep_goods_in_cart' );
				break;
			default:
				$days = apply_filters( 'bkng_get_session_lifetime', 14, $key );
				break;
		}

		return current_time( 'timestamp' ) + ( absint( $days ) * DAY_IN_SECONDS );
	}

	/**
	 * Fetch the storage expire time
	 * @since    0.1
	 * @access   protected
	 * @param    void
	 * @return   array               The list of storage data types
	 */
	protected static function get_keys() {
		return apply_filters(
			'bws_bkng_session_keys',
			array( 'cart' )
		);
	}

	/**
	 * Fetch the storage expire time
	 * @since    0.1
	 * @access   protected
	 * @param    string    $key    The data type
	 * @param    mixed     $data   Data which need to update
	 * @return   string            Prepared data
	 */
	protected function prepare( $key, $data ) {
		$data = apply_filters( "bws_bkng_{$key}_content_before_save", $data );
		return json_encode( $data );
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
