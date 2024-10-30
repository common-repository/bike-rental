<?php
/**
 * Loads the Booking core data and inits the WP entities
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Data_Loader' ) )
	return;

class BWS_BKNG_Data_Loader {

	/**
	 * Class constructor
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function __construct() {
		global $bws_bkng;

		add_filter( 'bws_bkng_args_filter', array( $this, 'check_args_filter' ), 10, 2 );
	}

	public function check_args_filter( $post_args, $post_type ) {
		global $bws_bkng_options, $wpdb;
		$flag_param = '';

		if ( empty( $bws_bkng_options ) ) {
			$bws_bkng_options = get_option( BWS_BKNG_PURE_SLUG . '_options' );
		}

		if ( isset( $post_type ) ) {
			switch ( $post_type ) {
				case 'bws_bike':
					$flag_param = $bws_bkng_options['cflag'];
					break;
				case 'bws_extra':
					$flag_param = $bws_bkng_options['eflag'];
					break;
			}
		}

		if ( ( isset( $bws_bkng_options['cflag'] ) || isset( $bws_bkng_options['eflag'] ) ) && $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE `post_type` = %s AND `post_status` != 'auto-draft'", $post_type ) ) >= base_convert( $flag_param, 16, 10 ) ) {
			$post_args['capabilities'] = array( 'create_posts' => 'do_not_allow' );
			$post_args['map_meta_cap'] = true;
		}
		return $post_args;
	}

	/**
	 * Loads files that contains some constant data
	 * @since  0.1
	 * @access public
	 * @param  string
	 * @return array|boolean
	 */
	public function load( $file_base_name ) {
		try {
			$file_base_name = sanitize_file_name( $file_base_name );
			return include( BWS_BKNG_PATH . 'data/' . $file_base_name . '.php' );
		} catch ( Exception $e ) {
			return false;
		}
	}

    /**
     * Load default plugin options
     * @since  0.1
     * @access public
     * @return false|void  $options   Value set for the option.
     */
	public function load_default_options() {
        global $bws_bkng;
        try {
            $default_options = include( BWS_BKNG_PATH . 'data/default_settings.php' );

            $post_types = $bws_bkng->get_post_types();

            foreach ( $post_types as $post_type ) {
                foreach ( $default_options['post_types_common_settings'] as $setting ) {
                    $default_options[ $post_type ][ $setting ] = $default_options[ $setting ];
                }
            }
            foreach ( $default_options['post_types_common_settings'] as $setting ) {
                unset( $setting );
            }

            return $default_options;
        } catch ( Exception $e ) {
            return false;
        }
    }

	/**
	 * Register new roles
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function init_roles() {
		global $bws_bkng;
		$customers = get_role( 'bws_bkng_customer' );
		if ( ! $customers ) {
			add_role(
				'bws_bkng_customer',
				__( 'Customer', BWS_BKNG_TEXT_DOMAIN ),
				array( 'read' => true )
			);
		}

		$agents = get_role( 'bws_bkng_agent' );
		if ( ! $agents ) {
			$agents = add_role(
				'bws_bkng_agent',
				__( 'Agent', BWS_BKNG_TEXT_DOMAIN ),
				array( 'read' => true )
			);
		}

		$admin = get_role( 'administrator' );

		foreach ( $bws_bkng->get_caps_list() as $cap ) {
			$admin->add_cap( $cap );
			$agents->add_cap( $cap );
		}
	}

	/**
	 * Fires during plugin initialization
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function register_booking_objects() {
		$this->register_post_types();
		$this->register_general_taxonomies();
	}

	/**
	 * Registers post types that are used in the plugin
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function register_post_types() {
		global $bws_post_type;

		if( ! isset( $bws_post_type ) || empty( $bws_post_type ) ){
			return;
		}

		/*
		 * Register the post type
		 */
		if( ! empty( $bws_post_type ) ){
			foreach( $bws_post_type as $post_type => $post_args ) {
				$post_args = apply_filters( 'bws_bkng_args_filter', $post_args, $post_type);
				register_post_type( $post_type, $post_args );
				add_action( 'save_post_' . $post_type, array( 'BWS_BKNG_Post_Metabox', 'save_post' ), 10, 2 );
				add_action( 'delete_post_' . $post_type, array( 'BWS_BKNG_Post_Metabox', 'delete_post' ) );
			}
		}
	}

	/**
	 * Registers taxonomies that are used in the plugin
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function register_general_taxonomies() {
		global $bws_bkng, $bws_taxonomies;

		if( ! isset( $bws_taxonomies ) || empty( $bws_taxonomies ) ){
			return;
		}

		/*
		 * Register products categories
		 */
		if( ! empty( $bws_taxonomies ) ){
			foreach( $bws_taxonomies as $post_type => $bws_taxonomy ) {
				foreach( $bws_taxonomy as $taxonomy_name => $taxonomy_args ){
					$args = apply_filters( 'bws_bkng_' . $taxonomy_name, $taxonomy_args );
					register_taxonomy( $taxonomy_name, $post_type, $taxonomy_args );
				}
			}
		}

		/**
		 * @see https://codex.wordpress.org/Function_Reference/register_post_type#Flushing_Rewrite_on_Activation
		 */
		flush_rewrite_rules( false );
	}

	/**
	 * Register metabox for the product attributes
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function register_metaboxes() {
		global $bws_bkng, $bws_metaboxes;

		if( ! isset( $bws_metaboxes ) || empty( $bws_metaboxes ) ) {
			return;
		}
		foreach( $bws_metaboxes as $post_type => $metabox_function ) {
			add_action( 'add_meta_boxes_' . $post_type, $metabox_function['metabox'] );
		}
	}

	/**
	 * Registers default plugin options if they don't exist
	 * @since  0.1
	 * @access public
	 * @param  mixed  $options   The default value to return if the option does not exist in the database.
	 * @return mixed  $options   Value set for the option.
	 */
	public function add_default_option( $options = false ) {
		global $bws_bkng;

		/* prevent the exccess process during making the update_option() function call */
		if ( ! $bws_bkng->is_get_option_call() || $bws_bkng->prevent_option_check )
			return $options;

		$bws_bkng->prevent_option_check = true;

		$this->create_db_tables();

		$default_options = apply_filters( 'bws_bkng_default_options', $this->load( 'default_settings' ) );

		$post_types = $bws_bkng->get_post_types();

		foreach ( $post_types as $post_type ) {
		    foreach ( $default_options['post_types_common_settings'] as $setting ) {
                $default_options[ $post_type ][ $setting ] = $default_options[ $setting ];
            }
        }
		foreach ( $default_options['post_types_common_settings'] as $setting ) {
		    unset( $setting );
        }

		add_option( $bws_bkng->plugin_prefix . '_options', $default_options );

		do_action( 'bws_bkng_default_options_installed', $default_options );

		return $default_options;
	}

	/**
	 * Update plugin options
	 * @since  0.1
	 * @access public
	 * @param  mixed  $options   Value of the option.
	 * @return mixed  $options   Value set for the option.
	 */
	public function upgrade_option( $options ) {
		global $bws_bkng;

		/**
		 * prevent the exccess process during making the update_option() function call or
		 * after the default plugin options loading
		 */
		if ( $bws_bkng->prevent_option_check )
			return $options;

		$info = $bws_bkng->get_plugin_info();
		$version = $bws_bkng->is_pro ? "pro-{$info['Version']}" : $info['Version'];

		if (
			! empty( $options ) &&
			! empty( $options['plugin_option_version'] ) &&
			$version == $options['plugin_option_version']
		)
			return $options;

		$bws_bkng->prevent_option_check = true;

		$options['plugin_option_version'] = $version;
		$options = apply_filters( 'bws_bkng_update_options', $options );

		$bws_bkng->update_option( $options );

		$this->create_db_tables();

		return $options;
	}

	/**
	 * Loads the Booking core database tables
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function create_db_tables() {
		global $wpdb, $bws_post_type;

		$current_post_types = array_keys( $bws_post_type );

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$prefix = BWS_BKNG_DB_PREFIX;

		/**
		 * Contains the dependencies between products
		 * 'post_id'          The main product ID
		 * 'product_id'       The product ID bind to the main
		 * 'category_id'      The category ID bind to the main product
		 * 'type'             The binding slug ( default: extras)
		 */
		$table = $prefix . 'linked_products';
		if ( ! $wpdb->query( 'SHOW TABLES LIKE "' . $table. '";' ) ) {
			$sql = 'CREATE TABLE `' . $table . '` (
				`post_id` BIGINT UNSIGNED NOT NULL,
				`product_id` INT UNSIGNED,
				`category_id` INT UNSIGNED NOT NULL,
				`type` TEXT NOT NULL
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
			dbDelta( $sql );
		}

		/**
		 * Contains users temporary data
		 * 'id    '            The database record ID
		 * 'user_id'           The logged in user ID
		 * 'key'               The storage slug ( e.g. 'cart')
		 * 'data'              The storage date create
		 * 'expires'           The date when the storage is need to be removed
		 */
		$table = $prefix . 'session';
		if ( ! $wpdb->query( 'SHOW TABLES LIKE "' . $table. '";' ) ) {
			$sql = 'CREATE TABLE `' . $table . '` (
				`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`user_id` INT UNSIGNED NOT NULL,
				`key` VARCHAR(255),
				`data` LONGTEXT,
				`expires` BIGINT UNSIGNED,
				PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
			dbDelta( $sql );
		}

		$subjects = array(
			'on_hold'   => '[{site_title}] Order on-hold',
			'processed' => '[{site_title}] Processed order',
			'completed' => '[{site_title}] Completed order',
			'canceled'  => '[{site_title}] Canceled order'
		);

		$messages = array(
			'customer_on_hold'   => '<p>Hi {customer_name},</p><p>Thank you for the order.</p><p>Your order number is <strong>{order_number}</strong> from <strong>{order_date}</strong>.</p><p>Order status: <strong>{order_status}</strong>.</p><p>Pick-up & Drop-off dates: <strong>{rent_interval}</strong>.</p><p>Ordered products:</p>{ordered_products}<p>Billing details:</p>{billing_details}',
			'customer_processed' => 'The order {order_number} was marked as being in process.',
			'customer_completed' => 'The order {order_number} was completed.',
			'customer_canceled'  => 'The order {order_number} was canceled.',
			'agent_on_hold'      => 'The customer {customer_name} placed an order {order_number}.',
			'agent_processed'    => 'The order {order_number} was marked as being in process.',
			'agent_completed'    => 'The order {order_number} was completed.',
			'agent_canceled'     => 'The order {order_number} was canceled.'
		);

		foreach( $current_post_types as $post_type ) {
			/**
			 * Contains the mail notifications data
			 * 'type'             The notifications type
			 *                        "terms_and_conditions" - The user's agreement are used on "Checkout" page,
			 *                        "customer_..."         - Notifications send to customers,
			 *                        "agent_..."            - Notifications send to agents,
			 * 'subject'          The notifications field "Subject" content
			 * 'body'             The notifications field message content
			 * 'enabled'          Whether the sending mails for this notifications type is enabled
			 */
			$table = $prefix . $post_type . '_notifications';
			if ( ! $wpdb->query( 'SHOW TABLES LIKE "' . $table. '";' ) ) {
				$sql = 'CREATE TABLE `' . $table . '` (
					`type` VARCHAR(255) NOT NULL UNIQUE,
					`subject` TEXT,
					`body` LONGTEXT,
					`enabled` TINYINT(1),
					`options` LONGTEXT
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
				dbDelta( $sql );
			}

			if ( ! $wpdb->query( 'SELECT `type` FROM `' . $table. '` LIMIT 1;' ) ) {


				$messages = apply_filters( 'bws_bkng_messages', $messages );

				$wpdb->query(
				    $wpdb->prepare(
                        'INSERT INTO `' . $table . '`
                        (`type`, `subject`, `body`, `enabled`) VALUES
                        ("terms_and_conditions", "", "", 1),
                        ("customer_on_hold_order", %s, %s, 1),
                        ("customer_processed_order", %s, %s, 1),
                        ("customer_completed_order", %s, %s, 1),
                        ("customer_canceled_order", %s, %s, 1),
                        ("agent_on_hold_order", %s, %s, 1),
                        ("agent_processed_order", %s, %s, 1),
                        ("agent_completed_order", %s, %s, 1),
                        ("agent_canceled_order", %s, %s, 1)
                        ON DUPLICATE KEY UPDATE `type`=`type`;',
                        $subjects['on_hold'], $messages['customer_on_hold'],
                        $subjects['processed'], $messages['customer_processed'],
                        $subjects['completed'], $messages['customer_completed'],
                        $subjects['canceled'], $messages['customer_canceled'],
                        $subjects['on_hold'], $messages['agent_on_hold'],
                        $subjects['processed'], $messages['agent_processed'],
                        $subjects['completed'], $messages['agent_completed'],
                        $subjects['canceled'], $messages['agent_canceled']
                    )
				);
			}

			/**
			 * Contains the general orders' data
			 * `id`                The order ID
			 * `status`            The order status slug
			 * `date_create`       The order date of creation
			 * `user_id`           The user's ID that placed the order
			 * `user_firstname`    The name specified by the user during placing the order
			 * `user_lastname`     The lastname specified by the user during placing the order
			 * `user_phone`        The phone number specified by the user during placing the order
			 * `user_email`        The e-mail specified by the user during placing the order
			 * `user_message`      The message specified by the user during placing the order
			 * `subtotal`          The order subtotal
			 * `total`             The order total
			 */
			$table = $prefix . $post_type . '_orders';
			if ( ! $wpdb->query( 'SHOW TABLES LIKE "' .$table . '";' ) ) {
				$sql = 'CREATE TABLE `' . $table . '` (
					`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
					`status` CHAR(64) NOT NULL,
					`date_create` DATETIME,
					`user_id` BIGINT NOT NULL,
					`user_firstname` VARCHAR(255) NOT NULL,
					`user_lastname` VARCHAR(255) NOT NULL,
					`user_phone` VARCHAR(255) NOT NULL,
					`user_email` VARCHAR(255) NOT NULL,
					`user_message` LONGTEXT,
					`subtotal` FLOAT NOT NULL,
					`total` FLOAT NOT NULL,
					PRIMARY KEY (`id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
				dbDelta( $sql );
			}

			/**
			 * The order additional data
			 * `order_id`      The order ID
			 * `meta_key`      The filed slug
			 * `meta_value`    The filed value
			 */
			$table = $prefix . $post_type . '_orders_meta';
			if ( ! $wpdb->query( 'SHOW TABLES LIKE "' . $table . '";' ) ) {
				$sql = 'CREATE TABLE `' . $table . '` (
					`order_id` BIGINT UNSIGNED NOT NULL,
					`meta_key` VARCHAR(255) NOT NULL,
					`meta_value` LONGTEXT
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
				dbDelta( $sql );
			}

			/**
			 * Contatins the ordered products list
			 * `id`                   The database record ID
			 * `product_id`           The product ID
			 * `order_id`             The order ID
			 * `linked_to`            The product ID to which the current is binded
			 * `rent_interval_from`   The product rent start date
			 * `rent_interval_till`   The product rent end date
			 * `rent_interval_step`   The product rent interval step
			 * `quantity`             The amount of ordered product instances
			 * `price`                The product price that was specified during placing the product
			 * `subtotal`             The product subtotal
			 * `total`                The product total
			 */
			$table = $prefix . $post_type . '_ordered_products';
			if ( ! $wpdb->query( 'SHOW TABLES LIKE "' . $table . '";' ) ) {
				$sql = 'CREATE TABLE `' . $table . '` (
					`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
					`product_id` BIGINT UNSIGNED NOT NULL,
					`order_id` BIGINT UNSIGNED NOT NULL,
					`linked_to` BIGINT UNSIGNED,
					`rent_interval_from` DATETIME,
					`rent_interval_till` DATETIME,
					`rent_interval_step` INT,
					`quantity` BIGINT UNSIGNED,
					`price` FLOAT NOT NULL,
					`subtotal` FLOAT NOT NULL,
					`total` FLOAT NOT NULL,
					PRIMARY KEY (`id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
				dbDelta( $sql );
			}
		}
	}

	/**
	 * Loads the custom database tables
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function create_custom_db_tables( $query ) {
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		/* call dbDelta */
		dbDelta( $query );
	}

	/**
	 * Loads the custom attribute database tables
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function create_attributes_db_tables() {
		global $wpdb, $bws_attributes_tables;
		if( ! isset( $bws_attributes_tables ) || empty( $bws_attributes_tables ) ){
			return;
		}
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$wpdb_collate = 'COLLATE ' . $wpdb->collate;

		foreach( $bws_attributes_tables as $post_type ) {
			/* create table conformity roles id with fields id */
			$sql = 'CREATE TABLE IF NOT EXISTS `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_ids` (
				`field_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
				`field_name` VARCHAR(150) NOT NULL ' . $wpdb_collate . ',
				`field_slug` VARCHAR(150) NOT NULL ' . $wpdb_collate . ' UNIQUE,
				`description` TEXT NOT NULL ' . $wpdb_collate . ',
				`field_type_id` BIGINT(20) NOT NULL DEFAULT "0",
				UNIQUE KEY ( `field_id` )
				);';
			/* call dbDelta */
			dbDelta( $sql );

			$sql = 'SHOW COLUMNS FROM `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_ids` LIKE "visible_status"';

			if( ! $wpdb->query( $sql ) ) {
				$wpdb->query( 'ALTER TABLE `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_ids` ADD `visible_status` INT(1) NOT NULL DEFAULT 1' );
			}


			/* create table conformity field id with available value */
			$sql = 'CREATE TABLE IF NOT EXISTS `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_values` (
				`value_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
				`field_id` BIGINT(20) NOT NULL DEFAULT "0",
				`value_name` VARCHAR(255) NOT NULL ' . $wpdb_collate . ',
				`order` BIGINT(20) NOT NULL DEFAULT "0",
				UNIQUE KEY ( `value_id` )
			);';
			/* call dbDelta */
			dbDelta( $sql );
			/* create table conformity field id with available value */
			$sql = 'CREATE TABLE IF NOT EXISTS `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_post_data` (
				id BIGINT(20) NOT NULL AUTO_INCREMENT,
				field_id BIGINT(20) NOT NULL,
				post_id BIGINT(20) NOT NULL,
				post_value VARCHAR(255) NOT NULL ' . $wpdb_collate . ',
				UNIQUE KEY (id)
			);';
			/* call dbDelta */
			dbDelta( $sql );
		}
	}

	/**
	 * Loads the data for attribute tables
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function add_data_attributes() {
		global $wpdb, $bws_bkng, $bws_metaboxes;
		if( ! isset( $bws_metaboxes ) || empty( $bws_metaboxes ) ){
			return;
		}
		foreach( $bws_metaboxes as $post_type => $data ) {
			if( ! empty( $data['general_labels'] ) ){
				foreach( $data['general_labels'] as $attribute_slug => $attribute_data ) {
					if( 0 == $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_ids` WHERE `field_slug` = %s', $bws_bkng->plugin_prefix . '_' . $attribute_slug ) ) ) {
						$name = $attribute_data['name'];
						if( is_array( $name ) ) {
							$name = serialize( $name );
						}
						$query = $wpdb->prepare( 'INSERT IGNORE INTO `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_ids`
							SET `field_name` = %s,
								`field_slug` = %s,
								`description` = %s,
								`field_type_id` = %d,
								`visible_status` = %d;',
                            $name,
                            $bws_bkng->plugin_prefix . '_' . $attribute_slug,
                            $attribute_data['description'],
                            $attribute_data['type_id'],
                            $attribute_data['visible_status']
                        );
						$wpdb->query( $query );
					}
				}
			}
			if( ! empty( $data['price_labels'] ) ){
				foreach( $data['price_labels'] as $attribute_slug => $attribute_data ) {
					if( 0 == $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_ids` WHERE `field_slug` = %s', $bws_bkng->plugin_prefix . '_' . $attribute_slug ) ) ) {
						$name = $attribute_data['name'];
						if( is_array( $name ) ) {
							$name = serialize( $name );
						}
						$query = $wpdb->prepare( 'INSERT IGNORE INTO `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_ids`
							SET `field_name` = %s,
								`field_slug` = %s,
								`description` = %s,
								`field_type_id` = %d,
								`visible_status` = %d;',
                            $name,
                            $bws_bkng->plugin_prefix . '_' . $attribute_slug,
                            $attribute_data['description'],
                            $attribute_data['type_id'],
                            $attribute_data['visible_status']
                        );
						$wpdb->query( $query );
					}
				}
			}
		}
	}

	/**
	 * Loads the custom attribute database tables
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function create_locations_db_tables() {
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$wpdb_collate = 'COLLATE ' . $wpdb->collate;

		$sql = 'CREATE TABLE IF NOT EXISTS `' . BWS_BKNG_DB_PREFIX . 'locations` (
			`location_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
			`location_name` VARCHAR(255) NOT NULL ' . $wpdb_collate . ',
			`location_address` VARCHAR(255) NOT NULL ' . $wpdb_collate . ',
			`location_latitude` CHAR(20) NOT NULL ' . $wpdb_collate . ',
			`location_longitude` CHAR(20) NOT NULL ' . $wpdb_collate . ',
			UNIQUE KEY ( `location_id` )
			);';
		/* call dbDelta */
		dbDelta( $sql );

		/* create table conformity field id with available value */
		$sql = 'CREATE TABLE IF NOT EXISTS `' . BWS_BKNG_DB_PREFIX . 'post_location` (
			`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
			`post_id` BIGINT(25) NOT NULL,
			`location_id` BIGINT(25) NOT NULL,
			`location_post_type` VARCHAR(255) NOT NULL ' . $wpdb_collate . ',
			UNIQUE KEY ( `id` )
		);';
		/* call dbDelta */
		dbDelta( $sql );
	}

	public function get_field_type_id() {
		/* Conformity between field type id and field type name */
		$field_type_id = array(
			'1' => __( 'Text field', BWS_BKNG_TEXT_DOMAIN ),
			'2' => __( 'Textarea', BWS_BKNG_TEXT_DOMAIN ),
			'3' => __( 'Checkbox', BWS_BKNG_TEXT_DOMAIN ),
			'4' => __( 'Radio button', BWS_BKNG_TEXT_DOMAIN ),
			'5' => __( 'Drop down list', BWS_BKNG_TEXT_DOMAIN ),
			'6' => __( 'Date', BWS_BKNG_TEXT_DOMAIN ),
			'7' => __( 'Time', BWS_BKNG_TEXT_DOMAIN ),
			'8' => __( 'Datetime', BWS_BKNG_TEXT_DOMAIN ),
			'9' => __( 'Number', BWS_BKNG_TEXT_DOMAIN )
		);
		return $field_type_id;
	}
}
