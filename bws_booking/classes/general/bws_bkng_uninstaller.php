<?php
/**
 * Removes the Booking core data during its deleting
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Helper' ) )
	return false;

class BWS_BKNG_Uninstaller {

	/**
	 * Conatains the current blog table prefix
	 * @since 0.1
	 * @access private
	 * @var string
	 */
	private $prefix;

	/**
	 * Inits the main class functionality
	 * @since    0.1
	 * @access   public
	 * @param    boolean    $blog_only     Whether to remove plugin data from the current blog
	 * @return   void
	 */
	public static function uninstall( $blog_only = false ) {
		global $wpdb, $bws_bkng;

		if ( empty( $bws_bkng ) && class_exists( 'BWS_BKRNTL' ) ) {
			BWS_BKRNTL::init_booking();
		}

		$instance = new self();

		if ( ! is_multisite() || $blog_only ) {
			$instance->clear_blog();
		} else {
			$old_blog = $wpdb->blogid;
			$blogids  = $wpdb->get_col( "SELECT `blog_id` FROM `{$wpdb->blogs}`;" );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				$instance->clear_blog();
			}
			switch_to_blog( $old_blog );
		}
	}

	/**
	 * Removes all data from the blog database tables
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function clear_blog() {
		global $bws_bkng, $wpdb;



		if ( ! empty( $bws_bkng->plugin_upgrade_versions ) ) {
			$all_plugins = get_plugins();

			foreach ( $bws_bkng->plugin_upgrade_versions as $version => $plugin ) {
				if ( array_key_exists( $plugin, $all_plugins ) )
					return;
			}
		}

		$this->prefix = $wpdb->get_blog_prefix() . 'bws_bkng_';

		$this->remove_caps();

		$demo = BWS_BKNG_Demo_Data_Loader::get_instance( true );

		if ( $demo->is_demo_installed() ) {
			$demo->remove();
		}

		$wpdb->query( 'DROP TABLE IF EXISTS `' . $this->prefix . 'notifications`, `' . $this->prefix . 'session`;' );

		delete_option( "{$bws_bkng->plugin_prefix}_options" );

		flush_rewrite_rules( false );
	}

	/**
	 * Removes users roles and capabilities which were added by the plugin
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function remove_caps() {
		global $bws_bkng;

		$role = get_role( 'administrator' );

		foreach ( $bws_bkng->get_caps_list() as $cap )
			$role->remove_cap( $cap );

		foreach( array( 'bws_bkng_agent', 'bws_bkng_customer' ) as $role ) {
			if ( get_role( $role ) )
				remove_role( $role );
		}
	}

	/**
	 * Remove orders database tables
     * @deprecated Not used since 1.0.2
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function remove_orders( $remove_options = null ) {
		global $wpdb, $bws_post_type;

		if (null !== $remove_options ) {
			foreach ( $remove_options as $post_type => $post_value ) {
				$wpdb->query( 'DROP TABLE IF EXISTS `' . $this->prefix . $post_type . '_orders`, `' . $this->prefix . $post_type . '_orders_meta`, `' . $this->prefix . $post_type . '_ordered_products`;' );
			}
		} else {
			$current_post_types = array_keys( $bws_post_type );

			foreach( $current_post_types as $post_type ) {
				$wpdb->query( 'DROP TABLE IF EXISTS `' . $this->prefix . $post_type . '_orders`, `' . $this->prefix . $post_type . '_orders_meta`, `' . $this->prefix . $post_type . '_ordered_products`;' );
			}
		}
	}

	/**
	 * Remove all attributes data from the database
     * @deprecated Not used since 1.0.2
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function remove_attributes() {
		global $wpdb, $bws_bkng;

		$wpdb->query( "DROP TABLE IF EXISTS `{$this->prefix}cat_att_dependencies`;" );

		$attributes = $bws_bkng->get_option( 'attributes' );
		$taxonomies = array();

		foreach ( $attributes as $slug => $data ) {
			if ( $bws_bkng->is_taxonomy( $data['meta_type'] ) )
				$taxonomies[] = $slug;
		}

		if ( ! empty( $taxonomies ) )
			$this->remove_taxonomies( $taxonomies );

		delete_option( 'bkng_attributes' );
	}

	/**
	 * Remove all products from the database
     * @deprecated Not used since 1.0.2
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function remove_products() {
		global $wpdb, $bws_bkng;

		$wpdb->query( "DROP TABLE IF EXISTS `{$this->prefix}linked_products`;" );

		$post_types = "'" . implode( "','", $bws_bkng->get_post_types() ) . "'";
		$products   = $wpdb->get_col( "SELECT `ID` FROM `{$wpdb->posts}` WHERE `post_type` IN ({$post_types});" );

		if ( ! empty( $products ) ) {
			$ids = implode( ',', $products );
			$wpdb->query( "DELETE FROM `{$wpdb->term_relationships}` WHERE `object_id` IN ({$ids});" );
			$wpdb->query( "DELETE FROM `{$wpdb->postmeta}` WHERE `post_id` IN ({$ids});" );
			$wpdb->query( "DELETE FROM `{$wpdb->posts}` WHERE `post_type` IN ({$post_types}) OR (`post_type`='revision' AND 'post_parent' IN ({$ids}));" );
		}

		$this->remove_taxonomies( array( BWS_BKNG_CATEGORIES, BWS_BKNG_AGENCIES, BWS_BKNG_TAGS ) );
	}

	/**
	 * Remove all products terms taxonomies from the database
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	private function remove_taxonomies( $taxonomies ) {
		global $wpdb, $bws_bkng;

		$slugs    = implode( "','", $taxonomies );
		$children = implode( "_children','", $taxonomies );
		$default  = implode( "','default_", $taxonomies );
		$terms    = $wpdb->get_col( "SELECT `term_id` FROM `{$wpdb->term_taxonomy}` WHERE `taxonomy` IN ('{$slugs}')" );

		$wpdb->query( "DELETE FROM `{$wpdb->options}` WHERE `option_name` IN ('{$children}_children') OR `option_name` IN ('default_{$default}');" );

		if ( empty( $terms ) )
			return;

		$terms = implode( ",", $terms );
		$wpdb->query( "DELETE FROM `{$wpdb->term_relationships}` WHERE `term_taxonomy_id` IN ({$terms});" );
		$wpdb->query( "DELETE FROM `{$wpdb->term_taxonomy}` WHERE `term_id` IN ({$terms});" );
		$wpdb->query( "DELETE FROM `{$wpdb->termmeta}` WHERE `term_id` IN ({$terms});" );
		$wpdb->query( "DELETE FROM `{$wpdb->terms}` WHERE `term_id` IN ({$terms});" );
	}
}
