<?php
/**
 * @uses     To handle atrributes page
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

if ( class_exists( 'BWS_BKNG_Locations_Page' ) )
	return;

class BWS_BKNG_Locations_Page extends WP_List_Table {
	private static $instance = NULL;

	/**
	 * Contains the list of registerd locations
	 * @since    0.1
	 * @access   private
	 * @var      array
	 */
	private $locations;

	/**
	 * Contains the list of product categories
	 * @since    0.1
	 * @access   private
	 * @var      array
	 */
	private $categories;

	/**
	 * Contains the slug of currently chosen category
	 * @since    0.1
	 * @access   private
	 * @var      string
	 */
	private $current_category;

	/**
	 * Contains the errors that occurs during managing locations - an instance of the class WP_Error
	 * @since    0.1
	 * @access   private
	 * @var      object
	 */
	private $errors;

	/**
	 * Contains the error codes that occurs during managing locations
	 * @uses     to highlight the input fields where user entered incorrect data
	 * @since    0.1
	 * @access   private
	 * @var      array
	 */
	private $error_codes;

	/**
	 * Contains the notice about action results for further displaying
	 * @since    0.1
	 * @access   private
	 * @var      string
	 */
	private $message;

	/**
	 * Contains the prefix for the attribute "name" of form inputs
	 * @since    0.1
	 * @access   private
	 * @var      string
	 */
	private $input_name           = 'bkng_edit_item';

	/**
	 * Contains the name of the $_REQUEST field that is used to
	 * filter locations by category
	 * @since    0.1
	 * @access   private
	 * @var      string
	 */
	private $filter_category_name = 'bkng_filter_category';

	/**
	 * Contains the name of the $_REQUEST field that is used to
	 * detect user action
	 * @since    0.1
	 * @access   private
	 * @var      string
	 */
	private $filter_name          = 'filter_action';

	/**
	 * Contains the list of dependecies between products locations and products categories
	 * @since    0.1
	 * @access   private
	 * @var      array
	 */
	private $dependencies         = array();

	/**
	 * Fetch the class instance
	 * @since    0.1
	 * @access   public
	 * @static
	 * @param    void
	 * @return   object    An instance of the current class
	 */
	public static function get_instance( $args ) {

		if ( ! self::$instance instanceof self )
			self::$instance = new self( $args );

		return self::$instance;
	}

	/**
	 * Class constructor
	 * @since    0.1
	 * @access   public
	 * @param    voids
	 * @return   void
	 */
	public function __construct(){
		global $bws_bkng;

		if( ! isset( $_GET['page'] ) || ( isset( $_GET['post_type'] ) && $bws_bkng->plugin_prefix . '_' . $_GET['post_type'] . '_locations' !== $_GET['page'] ) ) {
			return;
		}

		parent::__construct( array(
			'singular' => __( 'location', BWS_BKNG_TEXT_DOMAIN ),
			'plural'   => __( 'locations', BWS_BKNG_TEXT_DOMAIN ),
			'ajax'     => false
		));
		$this->locations       = $bws_bkng->get_option( 'locations' );
		$this->process_action();
	}

	/**
	 * Show message if item list is empty
	 * @since    0.1
	 * @access   public
	 * @param  void
	 * @return void
	 */
	public function no_items() { ?>
		<p><?php _e( 'No locations found', BWS_BKNG_TEXT_DOMAIN ); ?></p>
	<?php }

	/**
	 * Get the list of table columns.
	 * @since    0.1
	 * @access   public
	 * @see WP_List_Table::single_row_columns()
	 * @param  void
	 * @return array     An associative array containing column information: 'slugs'=>'Visible Titles'
	 */
	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'name'      => __( 'Name', BWS_BKNG_TEXT_DOMAIN ),
			'address'   => __( 'Address', BWS_BKNG_TEXT_DOMAIN ),
			'latitude'  => __( 'Latitude', BWS_BKNG_TEXT_DOMAIN ),
			'longitude' => __( 'Longitude', BWS_BKNG_TEXT_DOMAIN )

		);
		return $columns;
	}

	/**
	 * Get the list of sortable columns.
	 * @since    0.1
	 * @access   public
	 * @param  void
	 * @return array   An associative array containing all the columns
	 *                 that should be sortable: 'slugs'=>array('data_values',bool)
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'name' => array( 'name', false ),
		);
		return $sortable_columns;
	}

	/**
	 * @see WP_List_Table::single_row_columns()
	 * @since    0.1
	 * @access   public
	 *
	 * @param  array   $item		  A singular item (one full row's worth of data)
	 * @param  array   $column_name   The name/slug of the column to be processed
	 * @return string				 Text or HTML to be placed inside the column <td>
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'name':
			case 'address':
			case 'latitude':
			case 'longitude':
				return $item[ 'location_' . $column_name ];
			default:
				return print_r( $item, true );
		}
	}

	/**
	 * Manage content of column with checboxes
	 * @since    0.1
	 * @access   public
	 * @param	array	 $item		The current item data.
	 * @return	string				 with the column content
	 */
	public function column_cb( $item ) {
		global $bws_bkng;
		return '<input type="checkbox" name="location_id[]" value="' . $item['location_id'] . '" />';
	}

	/**
	 * Handles the content of "Title" column
	 * @since    0.1
	 * @access   public
	 * @see    WP_List_Table::single_row_columns()
	 * @param  array   $item   A singular item (one full row's worth of data)
	 * @return string          Text to be placed inside the column <td>
	 */
	public function column_name( $item ){

		$link       = '<a href="%1$s">%2$s</a>';
		$post_type = sanitize_text_field( stripslashes( $_GET['post_type'] ) );
		$page = sanitize_text_field( stripslashes( $_GET['page'] ) );
		$location_id = absint( $item['location_id'] );
		$action_url = admin_url( "edit.php?post_type={$post_type}&amp;page={$page}&amp;location_id={$location_id}&amp;action=" );
		$actions    = array();

		$actions['edit'] = sprintf( $link, $action_url . 'edit', __( 'Edit', BWS_BKNG_TEXT_DOMAIN ) );

		$actions['delete'] = sprintf( $link, $action_url . 'delete', __( 'Delete', BWS_BKNG_TEXT_DOMAIN ) );

		$title = sprintf( "<strong>{$link}</strong>", $action_url . 'edit', $item['location_name'] );

		return $title . $this->row_actions( $actions );
	}

	/**
	 * Handles the content of "Slug" column
	 * @since    0.1
	 * @access   public
	 * @see	WP_List_Table::single_row_columns()
	 * @param  array $item A singular item (one full row's worth of data)
	 * @return string Text to be placed inside the column <td>

	public function column_slug( $item ) {
		return $item['field_slug'];
	}*/

	/**
	 * Handles the content of "Type" column
	 * @since  0.1
	 * @access public
	 * @see	   WP_List_Table::single_row_columns()
	 * @param  array $item A singular item (one full row's worth of data)
	 * @return string Text to be placed inside the column <td>

	public function column_type( $item ) {
		global $bws_bkng;
		$field_type_id = $bws_bkng->data_loader->get_field_type_id();
		return sprintf(
			'%s', $field_type_id[ $item['field_type_id'] ]
		);
	}*/

	/**
	 * List with bulk actions
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return array  $actions  An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 */
	public function get_bulk_actions() {
		$actions = array( 'delete' => __( 'Delete', BWS_BKNG_TEXT_DOMAIN ) );
		return $actions;
	}

	/**
	 * Displays the additional form controls
	 * @since  0.1
	 * @access public
	 * @param  string      $which     In what place form controls will be displayed ('top' or 'bottom')
	 * @return void
	 */
	public function extra_tablenav( $which ) {}

	/**
	 * Handles the requested actions
	 * @see $this->prepare_items()
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function process_action() {
		global $wpdb, $bws_bkng;

		$action = $this->current_action();
		if( isset( $_POST['bws_bkng_save_field'] ) ) {
			$action = 'save';
		}
		$count  = 0;
		$error = '';

		if ( empty( $action ) )
			return;

		switch( $action ) {
			case 'delete':
				if ( empty( $_REQUEST['location_id'] ) ) {
					break;
			  }
				foreach ( (array)$_REQUEST['location_id'] as $location_id ) {
					$wpdb->delete(
						BWS_BKNG_DB_PREFIX . 'post_location',
						array(
							'location_id'	=> $location_id
						),
                        '%d'
					);
					$wpdb->delete(
						BWS_BKNG_DB_PREFIX . 'locations',
						array(
							'location_id' => $location_id
						),
                        '%d'
					);
				}
				wp_redirect( esc_url( remove_query_arg( array( 'location_id', 'action' ) ) ) );
				break;
			case 'save':
				$location_id		= ! empty( $_REQUEST['bws_bkng_location_id'] ) ? absint( $_REQUEST['location_id'] ) : NULL;
				$location_name		= ! empty( $_POST['bws_bkng_location_name'] ) ? sanitize_title( $_POST['bws_bkng_location_name'] ) : '';
				$location_address	= ! empty( $_POST['bws_bkng_location_address'] ) ? sanitize_text_field( $_POST['bws_bkng_location_address'] ) : '';
				$location_latitude	= ! empty( $_POST['bws_bkng_location_latitude'] ) ? floatval( $_POST['bws_bkng_location_latitude'] ) : '';
				$location_longitude	= ! empty( $_POST['bws_bkng_location_longitude'] ) ? floatval( $_POST['bws_bkng_location_longitude'] ) : '';

				if ( empty( $location_name ) ) {
					$this->errors .= sprintf( '<p><strong>%s</strong></p>', __( 'Field name is empty.', BWS_BKNG_TEXT_DOMAIN ) );
				}
				if ( empty( $location_address ) ) {
					$this->errors .= sprintf( '<p><strong>%s</strong></p>', __( 'Field address is empty.', BWS_BKNG_TEXT_DOMAIN ) );
				}
				if ( empty( $this->errors ) ) {
					/* Check for exist location id */
					$old_location_data = $wpdb->get_row(
                        $wpdb->prepare(
                            'SELECT * 
                            FROM `' . BWS_BKNG_DB_PREFIX . 'locations` 
                            WHERE `location_id`=%d',
                            $location_id
                        ), ARRAY_A );

					if ( null !== $old_location_data ) {
						/* Update data */
						$wpdb->update(
							BWS_BKNG_DB_PREFIX .  'locations',
							array(
								'location_name'			 => $location_name,
								'location_address'	 => $location_address,
								'location_latitude'	 => $location_latitude,
								'location_longitude' => $location_longitude,
							),
							array(
								'location_id'				 => $location_id
							)
						);
						$this->message = __( 'The location has been updated', BWS_BKNG_TEXT_DOMAIN );
					} else {
						$this->message = __( 'The location has been created', BWS_BKNG_TEXT_DOMAIN );
						/* Update data */
						$wpdb->insert(
							BWS_BKNG_DB_PREFIX . 'locations',
							array(
								'location_id'				 => $location_id,
								'location_name'			 => $location_name,
								'location_address'	 => $location_address,
								'location_latitude'	 => $location_latitude,
								'location_longitude' => $location_longitude,
							)
						);
					}
				}
				break;
			default:
				break;
		}
	}

	/**
	 * Prepares the list of table items before displaying
	 * @global WPDB $wpdb
	 * @access public
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 */
	public function prepare_items() {
		global $wpdb;
		$table_fields_id		= BWS_BKNG_DB_PREFIX . 'locations';
		$perpage = $this->get_items_per_page( 'locations_per_page', 20 );
		$current_page = $this->get_pagenum();
		$totalitems =  $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $table_fields_id );
		$totalpages = ceil( $totalitems / $perpage );
		/* Set pagination arguments */
		$this->set_pagination_args( array(
			'total_items'	=> $totalitems,
			'per_page'		=> $perpage
		) );
		/* Settings data to output */
		$this->_column_headers	= $this->get_column_info();
		/* Slice array */
		$this->items			= $this->get_items();
	}

		/**
	 * Fetch the list of registered locations
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return array
	 */
	private function get_items() {
		global $wpdb;
		$searchrequest = $orderrequest = '';
		$table_locations		= BWS_BKNG_DB_PREFIX . 'locations';
		/* Search handler */
		if ( isset( $_GET['s'] ) && '' != trim( $_GET['s'] ) ) {
			/* Sanitize search query */
			$searchrequest = sanitize_text_field( stripslashes( $_GET['s'] ) );
            $like = "%{$wpdb->esc_like( $searchrequest )}%";
			$searchrequest = "AND ( {$table_locations}.`location_name` LIKE '{$like}' OR {$table_locations}.`location_address` LIKE '{$like}' )";
		}
		/* Sort function */
		if ( isset( $_GET['orderby'] ) && isset( $_GET['order'] ) ) {
			/* Check permitted names of field */
            $order_by = sanitize_text_field( stripslashes( $_GET['orderby'] ) );
			switch ( $order_by ) {
				case 'name':
                    $order = sanitize_text_field( stripslashes( $_GET['order'] ) );
					$orderrequest = "ORDER BY {$table_locations}.`location_name` {$order}";
					break;
				default:
					$orderrequest = "ORDER BY {$table_locations}.`location_id` ASC";
					break;
			}
		}
		/* Get the value of number of field on one page */
		$perpage = $this->get_items_per_page( 'locations_per_page', 20 );
		$current_page = $this->get_pagenum();
		$limitquery = "LIMIT " . ( $current_page - 1 ) * $perpage . ",{$perpage}";

		$query = "SELECT * FROM {$table_locations} WHERE 1 = 1 {$searchrequest} {$orderrequest} {$limitquery}";
		$locations_query_result	= $wpdb->get_results( $query, ARRAY_A );
		return $locations_query_result;
	}


	/**
	 * Displays the list of registered locations
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function display_table() {
		global $title;

		$this->prepare_items(); ?>
		<div class="wrap">
			<h1>
				<?php echo esc_html( $title ); ?>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . sanitize_text_field( stripslashes( $_GET['post_type'] ) ) . '&amp;page=' . sanitize_text_field( stripslashes( $_GET['page'] ) ) . '&amp;action=add_new' ) ) ?>" class="page-title-action add-new-h2" >
                    <?php _e( 'Add New', BWS_BKNG_TEXT_DOMAIN ); ?>
                </a>
			</h1>
			<?php $this->show_notices(); ?>
			<form id="bkng_locations_form" method="post">
				<?php $this->display(); ?>
			</form>
		</div>
	<?php }

	public function display_page (){
		if( isset( $_GET['action'] ) ) {
			$this->display_action_page();
		} else {
			$this->display_table();
		}
	}

	/**
	 * Displays the list of registered locations
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function display_action_page() {
		global $title, $bws_bkng, $wpdb;

		if( isset( $_GET['action'] ) ) {
            $name_of_page = '';
			if( isset( $_REQUEST['location_id'] ) ){
				$name_of_page = __( 'Edit Location', BWS_BKNG_TEXT_DOMAIN );
				$location_id = isset( $_REQUEST['location_id'] ) ? absint( $_REQUEST['location_id'] ) : NULL;
				$location_options = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . BWS_BKNG_DB_PREFIX . 'locations` WHERE `location_id`=%d', $location_id ), ARRAY_A );
				if ( ! $location_options ) {
					/* If entry not exist - create new entry */
					$location_id			= NULL;
					$location_name		    = '';
					$location_address		= '';
					$location_latitude		= '';
					$location_longitude		= '';
				} else {
					$location_name		    = $location_options['location_name'];
					$location_address			= $location_options['location_address'];
					$location_latitude		= $location_options['location_latitude'];
					$location_longitude		= $location_options['location_longitude'];
				}
			} elseif ( isset( $_GET['action'] ) && 'add_new' == $_GET['action'] ) {
				$name_of_page = __( 'Add New Location', BWS_BKNG_TEXT_DOMAIN );
				$location_id			= NULL;
				$location_name		    = '';
				$location_address		= '';
				$location_latitude		= '';
				$location_longitude		= '';
			}

			/* If field id is NULL - create new entry */
			if ( is_null( $location_id ) ) {
				if ( ! $location_id = $wpdb->get_var( 'SELECT MAX(`location_id`) FROM `' . BWS_BKNG_DB_PREFIX . 'locations`' ) ) {
					/* If table is empty */
					$location_id = 1;
				} else {
					/* Generate new id */
					$location_id++;
				}
			}
			$action = admin_url( 'edit.php?post_type=' . sanitize_text_field( stripslashes( $_GET['post_type'] ) ) . '&page=' . $bws_bkng->plugin_prefix . '_' . sanitize_text_field( stripslashes( $_GET['post_type'] ) ) . '_locations&location_id=' . $location_id . '&action=edit' ); ?>
			<div class="wrap">
				<h1>
					<?php echo esc_html( $name_of_page ); ?>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . sanitize_text_field( stripslashes( $_GET['post_type'] ) ) . '&amp;page=' . sanitize_text_field( stripslashes( $_GET['page'] ) ) . '&amp;action=add_new' ) ) ?>" class="page-title-action add-new-h2" >
                        <?php _e( 'Add New', BWS_BKNG_TEXT_DOMAIN ); ?>
                    </a>
				</h1>
				<?php $this->show_notices(); ?>
				<form class="bws_form" method="post" action="<?php echo esc_attr( $action ); ?>">
					<table class="form-table bkng_meta_input_wrap">
						<tbody>
							<tr>
								<th><?php _e( 'Name', BWS_BKNG_TEXT_DOMAIN ); ?></th>
								<td>
									<input class="regular-text" type="text" name="bws_bkng_location_name" value="<?php echo esc_attr( $location_name ); ?>" />
								</td>
							</tr>
							<?php $class   = "bkng_address_input regular-text";
							$name    = "bws_bkng_location_address";
							$value   = $location_address;
							$address_input = $bws_bkng->get_text_input( compact( 'class', 'name', 'value' ) );

							$unit  = 'button';
							$class = "button bkng_find_by_address_button";
							$value = __( 'Find by address', BWS_BKNG_TEXT_DOMAIN );
							$find_by_address_button = $bws_bkng->get_form_unit( compact( 'unit', 'class', 'value' ) );

							$address_errors = $bws_bkng->get_errors( '', '', "inline bkng_js_errors bkng_find_by_address_error " . BWS_BKNG::$hidden );
							//$content = '<p>' . $address_input . '</p><p>' . $find_by_address_button . '</p><p>' . $address_errors . '</p>';
							$content = $address_input . $find_by_address_button . $address_errors;

							$bws_bkng->display_table_row( __( 'Address', BWS_BKNG_TEXT_DOMAIN ), $content, 'bkng_map_extra_options ' ); // . BWS_BKNG::$hidden );

							/**
							 * Google map wrapper
							 */
							$content = '<div id="bkng_map_wrap" class="bkng_map_wrap"></div>';

							$bws_bkng->display_table_row( '', $content, 'bkng_map_extra_options ' ); // . BWS_BKNG::$hidden );

							/**
							 * Google map coordinates options
							 */
							$coors_errors = $bws_bkng->get_errors( '', '', 'inline bkng_js_errors bkng_find_by_coors_error ' . BWS_BKNG::$hidden );

							$unit  = 'button';
							$class = "button bkng_find_by_coordinates_button";
							$value = __( 'Find by coordinates', BWS_BKNG_TEXT_DOMAIN );
							$find_by_coors_button = $bws_bkng->get_form_unit( compact( 'unit', 'class', 'value' ) );

							$text_fields_names = array(
								'latitude'  => array( __( 'Latitude', BWS_BKNG_TEXT_DOMAIN ), $location_latitude ),
								'longitude' => array( __( 'Longitude', BWS_BKNG_TEXT_DOMAIN ), $location_longitude )
							);
							foreach ( $text_fields_names as $field_name => $data ) {
								$after  = $data[0];
								$class  = "bkng_{$field_name}_input regular-text";
								$name   = "bws_bkng_location_{$field_name}";
								$value  = $data[1];
								$$field_name = $bws_bkng->get_text_input( compact( 'after', 'class', 'name', 'value' ) );
							}

							$content = "{$coors_errors}<p>{$latitude}</p><p>{$longitude}</p><p>{$find_by_coors_button}</p>";

							$bws_bkng->display_table_row( __( 'Coordinates', BWS_BKNG_TEXT_DOMAIN ), $content, 'bkng_map_extra_options ' );  ?>
						</tbody>
					</table>
					<p class="submit">
						<input type="hidden" name="bws_bkng_save_field" value="true" />
						<input type="hidden" name="bws_bkng_location_id" value="<?php echo esc_attr( $location_id ); ?>" />
						<input id="bws-submit-button" type="submit" class="button-primary" name="bws_bkng_save_settings" value="<?php _e( 'Save Changes', BWS_BKNG_TEXT_DOMAIN ); ?>" />
						<?php wp_nonce_field( 'bws_bkng_nonce_name' ); ?>
					</p>
				</form>
			</div>
		<?php }
	}

	/**
	 * Sanitizes the location slug
	 * @since  0.1
	 * @access private
	 * @param  string     $code      The error code
	 * @param  string     $message   The error text
	 * @return void
	 */
	private function add_error( $code, $message ) {
		if ( ! is_wp_error( $this->errors ) )
			$this->errors = new WP_Error();
		$this->errors->add( $code, $message );
	}

	/**
	 * Displays notice messages
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return void
	 */
	private function show_notices() {
		if ( is_wp_error( $this->errors ) ) {
			$errors = implode( '<br />', $this->errors->get_error_messages() ); ?>
			<div class="error inline"><p><?php echo $errors; ?></p></div>
		<?php }

		if ( isset( $_GET['result'] ) && isset( $_GET['count'] ) ) {

			$formated = number_format_i18n( $_GET['count'] );
			$count    = absint( $_GET['count'] );

			if ( $count ) {
				switch ( $_GET['result'] ) {
					case 'delete':
						$this->message = sprintf(
							_n(
								"One location deleted",
								"%s locations deleted",
								$formated,
								BWS_BKNG_TEXT_DOMAIN
							),
							$count
						);
						break;
					case 'save':
						$this->message = __( "Location updated", BWS_BKNG_TEXT_DOMAIN );
						break;
					default:
						break;
				}
			}
		}

		if ( ! empty( $this->message ) ) { ?>
			<div class="updated fade inline"><p><?php echo esc_html( $this->message ); ?>.</p></div>
		<?php }
	}
}
