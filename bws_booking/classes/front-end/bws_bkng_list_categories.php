<?php
/**
 * Contains the list of terms data of the "bws_bkng_categories" taxonomy.
 * Data added to this class instanse gradually during running the plugin scripts.
 * It's some kind of buffer that keeps the list term data in order to reduce amounts of database queries.
 *
 * @uses     on the product single page  during the displaying of products data and the list of extras
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_List_Categories' ) )
	return;

class BWS_BKNG_List_Categories {

	/**
	 * Contains an instance of the current class
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    object
	 */
	private static $instance;

	/**
	 * Contains the term data list of the taxonomy "bws_bkng_categories"
	 * @since  0.1
	 * @access private
	 * @var    array
	 */
	private $categories = array();

	/**
	 * Fetch the class instance
	 * @since    0.1
	 * @access   public
	 * @static
	 * @param    void
	 * @return   object    An instance of the current class
	 */
	public static function get_instance() {

		if ( ! self::$instance instanceof self )
			self::$instance = new self();

		return self::$instance;
	}

	/**
	 * Fetch all categories data
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   array
	 */
	public function get_all_categories() {
		return $this->categories;
	}

	/**
	 * Fetch the specified category
	 * @since    0.1
	 * @access   public
	 * @param    int      $id           The ID of term taxonomy
	 * @param    string   $property     The name of the property whose value is to be returned
	 * @return   mixed                  The propery value if the property name is specified,
	 *                                  whole list of the category data if the property name isn't specified,
	 *                                  false if no category found
	 */
	public function get_category( $id, $property = '' ) {

		$id = absint( $id );

		if ( empty( $id ) )
			return false;

		if ( ! empty( $this->categories[ $id ] ) )
			return $this->get_category_data( $id, $property );

		$category = get_term_by( 'id', $id, BWS_BKNG_CATEGORIES );

		if ( $category instanceof WP_Term ) {
			$this->add_category( $category );
			return $this->get_category_data( $id, $property );
		}

		return false;
	}

	/**
	 * Adds the category data to storage
	 * @since    0.1
	 * @access   public
	 * @param    object        $category     The instance of WP_TERM class, term taxonomy data
	 * @return   int|boolean                 The ID of term taxonomy if data was added successfully, false otherwise
	 */
	public function add_category( $category ) {

		if ( ! $category instanceof WP_Term )
			return false;

		if ( empty( $this->categories[ $category->term_id ] ) )
			$this->categories[ $category->term_id ] = $category;

		return $category->term_id;
	}

	/**
	 * Fetch the specified category data
	 * @since    0.1
	 * @access   private
	 * @param    int      $id           The ID of term taxonomy
	 * @param    string   $property     The name of the property whose value is to be returned
	 * @return   mixed                  The category data
	 */
	private function get_category_data( $id, $property ) {
		return empty( $property ) || empty( $this->categories[ $id ]->$property ) ? $this->categories[ $id ] : $this->categories[ $id ]->$property;
	}

	/**
	 * Some magic to avoid the creation of several instances of this class
	 * @since    0.1
	 * @access   private
	 * @param    void
	 * @return   void
	 */
	private function __construct() {}
	private function __clone()     {}
	private function __sleep()     {}
	private function __wakeup()    {}

}