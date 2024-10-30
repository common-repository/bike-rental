<?php
/**
 * @uses     To keep data in database.
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_DB_Session' ) )
	return;

class BWS_BKNG_DB_Session extends BWS_BKNG_Session {

	/**
	 * Contains current user ID
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    int
	 */
	private $user_id;

	/**
	 * Contains the table name which keep session data
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    string
	 */
	private $table;

	/**
	 * Fetch the class instance
	 * @since    0.1
	 * @access   public
	 * @static
	 * @param    void
	 * @return   object           An instance of the current class
	 */
	public static function get_instance( $is_forced_cookie = false ) {
		if ( ! self::$storage_handler instanceof self )
			self::$storage_handler = new self();

		return self::$storage_handler;
	}

	/**
	 * Adds data to the database
	 * @abstract
	 * @since    0.1
	 * @access   public
	 * @param    string    $key    The data type
	 * @param    mixed     $data   Data which need to add to the storage
	 * @return
	 */
	protected function add_to_storage( $key, $data ) {
		global $wpdb;

		return $wpdb->insert(
			$this->table,
			array(
				'user_id' => $this->user_id,
				'key'     => esc_sql( $key ),
				'data'    => esc_sql( $data ),
				'expires' => $this->get_expire_time( $key )
			)
		);
	}

	/**
	 * Fetch data from the database
	 * @abstract
	 * @since    0.1
	 * @access   public
	 * @param    string    $key    The data type
	 * @return
	 */
	protected function get_from_storage( $key ) {
		global $wpdb;

		$key = esc_sql( $key );

		return $wpdb->get_var(
		    $wpdb->prepare(
                "SELECT `data`
                FROM `{$this->table}`
                WHERE `user_id`=%d
                    AND `key`=%s
                LIMIT 1;",
                $this->user_id,
                $key
            )
		);
	}

	/**
	 * Updates data in the database
	 * @abstract
	 * @since    0.1
	 * @access   public
	 * @param    string    $key    The data type
	 * @param    mixed     $data   Data to replace the current ones in the database
	 * @return
	 */
	protected function update_storage( $key, $data ) {
		global $wpdb;

		$result = $wpdb->update(
			$this->table,
			array(
				'data'    => esc_sql( $data ),
				'expires' => $this->get_expire_time( $key )
			),
			array(
				'user_id' => $this->user_id,
				'key'     => esc_sql( $key )
			)
		);

		if ( $result )
			return true;

		return $this->add_to_storage( $key, $data );
	}


	/**
	 * Removes data from the database
	 * @abstract
	 * @since    0.1
	 * @access   public
	 * @param    string    $key    The data type
	 * @return
	 */
	protected function remove_from_storage( $key ) {
		global $wpdb;

		return $wpdb->delete(
			$this->table,
			array(
				'user_id' => $this->user_id,
				'key'     => esc_sql( $key )
			)
		);
	}

	/**
	 * Class constructor
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   void
	 */
	private function __construct() {
		$this->user_id = get_current_user_id();
		$this->table   = BWS_BKNG_DB_PREFIX . 'session';
	}

	/**
	 * Some magic to avoid the creation of several instances of this class
	 * @since  0.1
	 */
	private function __clone()  {}
	private function __sleep()  {}
	private function __wakeup() {}
}