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

if ( class_exists( 'BWS_BKNG_Attributes_Page' ) )
	return;

class BWS_BKNG_Attributes_Page extends WP_List_Table {
	private static $instance = NULL;

	/**
	 * Contains the list of registered attributes
	 * @since    0.1
	 * @access   private
	 * @var      array
	 */
	private $attributes;

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
	 * Contains the errors that occurs during managing attributes - an instance of the class WP_Error
	 * @since    0.1
	 * @access   private
	 * @var      object
	 */
	private $errors;

	/**
	 * Contains the error codes that occurs during managing attributes
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
	 * filter attributes by category
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
	 * Contains the list of dependecies between products attributes and products categories
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

		if( ! isset( $_GET['page'] ) || ( isset( $_GET['post_type'] ) && $bws_bkng->plugin_prefix . '_' . $_GET['post_type'] . '_attributes' !== $_GET['page'] ) ) {
			return;
		}

		parent::__construct( array(
			'singular' => __( 'attribute', BWS_BKNG_TEXT_DOMAIN ),
			'plural'   => __( 'attributes', BWS_BKNG_TEXT_DOMAIN ),
			'ajax'     => false
		));
		$this->attributes       = $bws_bkng->get_option( 'attributes' );
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
		<p><?php _e( 'No attributes found', BWS_BKNG_TEXT_DOMAIN ); ?></p>
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
			'cb'          => '<input type="checkbox" />',
			'title'       => __( 'Name', BWS_BKNG_TEXT_DOMAIN ),
			'slug'        => __( 'Slug', BWS_BKNG_TEXT_DOMAIN ),
			'type'        => __( 'Type', BWS_BKNG_TEXT_DOMAIN ),
			'description' => __( 'Description', BWS_BKNG_TEXT_DOMAIN )
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
			'title' => array( 'title', false ),
			/*'slug'  => array( 'slug',  false ),*/
			'type'  => array( 'type',  false )
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
			case 'slug':
			case 'description':
				return $item[ $column_name ];
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
		return '<input type="checkbox" name="field_id[]" value="' . $item['field_id'] . '" />';
	}

	/**
	 * Handles the content of "Title" column
	 * @since    0.1
	 * @access   public
	 * @see    WP_List_Table::single_row_columns()
	 * @param  array   $item   A singular item (one full row's worth of data)
	 * @return string          Text to be placed inside the column <td>
	 */
	public function column_title( $item ){
		$link       = '<a href="%1$s">%2$s</a>';
		$post_type  = sanitize_text_field( stripslashes( $_GET['post_type'] ) );
		$page       = sanitize_text_field( stripslashes( $_GET['page'] ) );
		$field_id   = absint( $item['field_id'] );
		$action_url = admin_url( "edit.php?post_type={$post_type}&amp;page={$page}&amp;field_id={$field_id}&amp;action=" );
		$actions    = array();

		$actions['edit'] = sprintf( $link, $action_url . 'edit', __( 'Edit', BWS_BKNG_TEXT_DOMAIN ) );

		$actions['delete'] = sprintf( $link, $action_url . 'delete', __( 'Delete', BWS_BKNG_TEXT_DOMAIN ) );

		$title = sprintf( "<strong>{$link}</strong>", $action_url . 'edit', $item['field_name'] );
		return $title . $this->row_actions( $actions );
	}

	/**
	 * Handles the content of "Slug" column
	 * @since    0.1
	 * @access   public
	 * @see	WP_List_Table::single_row_columns()
	 * @param  array $item A singular item (one full row's worth of data)
	 * @return string Text to be placed inside the column <td>
	 */
	public function column_slug( $item ) {
		return $item['field_slug'];
	}

	/**
	 * Handles the content of "Type" column
	 * @since  0.1
	 * @access public
	 * @see	   WP_List_Table::single_row_columns()
	 * @param  array $item A singular item (one full row's worth of data)
	 * @return string Text to be placed inside the column <td>
	 */
	public function column_type( $item ) {
		global $bws_bkng;
		$field_type_id = $bws_bkng->data_loader->get_field_type_id();
		return sprintf(
			'%s', $field_type_id[ $item['field_type_id'] ]
		);
	}

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
		global $wpdb;

		$action = $this->current_action();
		if( isset( $_POST['bws_bkng_save_field'] ) ) {
			$action = 'save';
		}

		if ( empty( $action ) )
			return;
		switch( $action ) {
			case 'delete':
				if ( empty( $_REQUEST['field_id'] ) )
					break;
				foreach ( (array)$_REQUEST['field_id'] as $field_id ) {
				    $post_type = sanitize_text_field( stripslashes( $_GET['post_type'] ) );
					$wpdb->delete(
						BWS_BKNG_DB_PREFIX . $post_type . '_field_values',
						array(
							'field_id'	=> $field_id
						)
					);
					$wpdb->delete(
						BWS_BKNG_DB_PREFIX . $post_type . '_field_ids',
						array(
							'field_id'				=> $field_id
						)
					);
					$wpdb->delete(
						BWS_BKNG_DB_PREFIX . $post_type . '_field_post_data',
						array(
							'field_id'				=> $field_id
						)
					);
				}
				wp_redirect( remove_query_arg( array( 'field_id', 'action' ) ) );
				break;
			case 'save':
				$field_id			= ! empty( $_REQUEST['bws_bkng_field_id'] ) ? absint( $_REQUEST['bws_bkng_field_id'] ) : NULL;
				$field_name			= ! empty( $_POST['bws_bkng_field_name'] ) ? sanitize_text_field( stripslashes( $_POST['bws_bkng_field_name'] ) ) : '';
				$description		= ! empty( $_POST['bws_bkng_description'] ) ? sanitize_text_field( stripslashes( $_POST['bws_bkng_description'] ) ) : '';
				$field_type_id	    = ! empty( $_POST['bws_bkng_field_type_id'] ) ? absint( $_POST['bws_bkng_field_type_id'] ) : NULL;
				$field_slug			= ! empty( $_POST['bws_bkng_field_slug'] ) && '' != $_POST['bws_bkng_field_slug'] ? sanitize_title_with_dashes( $_POST['bws_bkng_field_slug'] ) : sanitize_title_with_dashes( $_POST['bws_bkng_field_name'] );
				$field_maxlength    = ! empty( $_POST['bws_bkng_maxlength'] ) ? absint( $_POST['bws_bkng_maxlength'] ) : '';
				$field_rows         = ! empty( $_POST['bws_bkng_rows'] ) ? absint( $_POST['bws_bkng_rows'] ) : '';
				$field_cols         = ! empty( $_POST['bws_bkng_cols'] ) ? absint( $_POST['bws_bkng_cols'] ) : '';

				if ( isset( $_POST['bws_bkng_time_format'] ) ) {
					$field_time_format = ( isset( $_POST['bws_bkng_time_format_custom'] ) && 'custom' == $_POST['bws_bkng_time_format'] ) ? sanitize_text_field( $_POST['bws_bkng_time_format_custom'] ) : sanitize_text_field( $_POST['bws_bkng_time_format'] );
				}
				if ( isset( $_POST['bws_bkng_date_format'] ) ) {
					$field_date_format = ( isset( $_POST['bws_bkng_time_format_custom'] ) && 'custom' == $_POST['bws_bkng_date_format'] ) ? sanitize_text_field( $_POST['bws_bkng_date_format_custom'] ) : sanitize_text_field( $_POST['bws_bkng_date_format'] );
				}
				if ( isset( $_POST['bws_bkng-value-delete'] ) ) {
					$field_value_to_delete = sanitize_text_field( stripslashes( $_POST['bws_bkng-value-delete'] ) );
				}
				$i = 1;
				if ( isset( $_POST['bws_bkng_available_values'] ) && is_array( $_POST['bws_bkng_available_values'] ) ) {
					$nonsort_available_values	= array_map( 'stripslashes_deep', $_POST['bws_bkng_available_values'] );
					$value_ids					= isset( $_POST['bws_bkng_value_id'] ) ? array_map( 'intval', $_POST['bws_bkng_value_id'] ) : '';
					/* is array */
					foreach ( $nonsort_available_values as $key => $value ) {
						if ( '' != $value ) {
							$available_values[]	= array(
								'value_name'	=> esc_html( $value ),
								'value_id'		=> in_array( $key, $value_ids ) ? $key : '',
								'value_order'	=> $i
							);
							$i++;
						} elseif ( in_array( $key, $value_ids ) ) {
							/* If field empty - delete entry */
							$field_value_to_delete[] = $key;
						}
					}
				}
				if ( empty( $field_name ) ) {
					$this->errors .= sprintf( '<p><strong>%s</strong></p>', __( 'Field name is empty.', BWS_BKNG_TEXT_DOMAIN ) );
				}
				if ( in_array( $field_type_id, array( '3', '4', '5' ) ) && ! empty( $_POST['bws_bkng_available_values'] ) ) {
					/* If not choisen values */
					if ( isset( $available_values ) && is_array( $available_values ) ) {
						/* if all values is empty */
						if ( 0 == count( $available_values ) ) {
							$this->errors .= sprintf( '<p><strong>%s</strong></p>', __( 'Select at least one available value.', BWS_BKNG_TEXT_DOMAIN ) );
						} elseif ( 2 > count( $available_values ) && ( 4 == $field_type_id || 5 == $field_type_id ) ) {
							/* If is radiobutton or select, select more if two available values */
							$this->errors .= sprintf( '<p><strong>%s</strong></p>', __( 'Select at least two available values.', BWS_BKNG_TEXT_DOMAIN ) );
						}
					} else {
						$this->errors .= sprintf( '<p><strong>%s</strong></p>', __( 'Select at least one available value.', BWS_BKNG_TEXT_DOMAIN ) );
					}
				}
				if ( empty( $this->errors ) ) {
					/* Check for exist field id */
                    $post_type = sanitize_text_field( stripslashes( $_GET['post_type'] ) );
					$old_field_data = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_ids` WHERE `field_id`=%d', $field_id ), ARRAY_A );
					if ( null !== $old_field_data ) {
						$this->message = __( 'The field has been updated', BWS_BKNG_TEXT_DOMAIN );
						/* Update data */
						$wpdb->update(
							BWS_BKNG_DB_PREFIX . $post_type . '_field_ids',
							array(
								'field_name'			=> $field_name,
								'field_slug'			=> $field_slug,
								'description'			=> $description,
								'field_type_id'		    => $field_type_id,
							),
							array(
								'field_id'				=> $field_id
							)
						);
					} else {
						$this->message = __( 'The field has been created', BWS_BKNG_TEXT_DOMAIN );
                        /* Update data */
						$wpdb->insert(
							BWS_BKNG_DB_PREFIX . $post_type . '_field_ids',
							array(
								'field_id'				=> $field_id,
								'field_name'			=> $field_name,
								'field_slug'			=> $field_slug,
								'description'			=> $description,
								'field_type_id'		=> $field_type_id,
							)
						);
					}


					if( null !== $old_field_data && $old_field_data['field_type_id'] != $field_type_id ) {
                        $wpdb->delete(
							BWS_BKNG_DB_PREFIX . $post_type . '_field_values',
							array(
								'field_id'	=> $field_id
							)
						);
					}
					/* bws_bkng_field_values update */
					if ( '1' == $field_type_id ||
                        '2' == $field_type_id ||
						'6' == $field_type_id ||
						'7' == $field_type_id ||
						'8' == $field_type_id ||
						'9' == $field_type_id ) {
						switch ( $field_type_id ) {
							case '1':
								$value_name = $field_maxlength;
								break;
							case '2':
								$value_name = serialize(
									array(
										'rows'       => $field_rows,
										'cols'       => $field_cols,
										'max_length' => $field_maxlength
									)
								);
								break;
							case '9':
								$value_name = $field_maxlength;
								break;
							case '6':
								$value_name = $field_date_format;
								break;
							case '7':
								$value_name = $field_time_format;
								break;
							case '8':
								$value_name = serialize( array( 'date' => $field_date_format, 'time' => $field_time_format ) );
								break;
						}
						/* If entry with current id not exist, create new entry */
                       if ( $wpdb->get_var( 'SELECT `value_id` FROM `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_values` WHERE `field_id`=' . $field_id ) ) {
							if ( '' != $value_name ) {
							    $wpdb->update(
									BWS_BKNG_DB_PREFIX . $post_type . '_field_values',
									array( 'value_name' => $value_name ),
									array( 'field_id' => $field_id )
								);
							} else {
                                $wpdb->delete(
									BWS_BKNG_DB_PREFIX . $post_type . '_field_values',
									array(
										'field_id'	=> $field_id
									)
								);
							}
						} elseif ( '' != $value_name ) {
                            $wpdb->insert(
								BWS_BKNG_DB_PREFIX . $post_type . '_field_values',
								array(
									'value_name'	=> $value_name,
									'field_id'		=> $field_id,
								)
							);
						}
					} elseif ( ! empty( $available_values ) ) {
						foreach ( $available_values as $i => $value ) {
							/* If entry with current id exists, update it */
							if ( ! empty( $value['value_id'] ) ) {
                                /* Update entry if not empty field (rename entry) */
								$wpdb->update(
									BWS_BKNG_DB_PREFIX . $post_type . '_field_values',
									array(
										'value_name'	=> $value['value_name'],
										'order'			=> $value['value_order']
									),
									array( 'value_id' => $value['value_id'] )
								);
							} else {
                                /* If entry with current id not exist, create new entry */
								$result_id = $wpdb->insert(
									BWS_BKNG_DB_PREFIX . $post_type . '_field_values',
									array(
										'value_name'	=> $value['value_name'],
										'field_id'		=> $field_id,
										'order'			=> $value['value_order']
									)
								);
								$available_values[ $i ]['value_id'] = $result_id;
							}
						}
					}
					/* Delete fields if necessary */
					if ( ! empty( $field_value_to_delete ) && is_array( $field_value_to_delete ) ) {
						foreach ( $field_value_to_delete as $deleting_value_id ) {
							if ( '' != $deleting_value_id ) {
                                /* remove field */
								$wpdb->delete(
									BWS_BKNG_DB_PREFIX . $post_type . '_field_values',
									array(
										'value_id' => $deleting_value_id,
									)
								);
							}
						}
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
		$table_fields_id		= BWS_BKNG_DB_PREFIX . sanitize_text_field( stripslashes( $_GET['post_type'] ) ) . '_field_ids';
		/* Get the value of number of field on one page */
		$perpage = $this->get_items_per_page( 'fields_per_page', 20 );
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
	 * Fetch the list of registered attributes
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return array
	 */
	private function get_items() {
		global $wpdb;
		$searchrequest = $orderrequest = '';
		$table_fields_id = BWS_BKNG_DB_PREFIX . sanitize_text_field( stripslashes( $_GET['post_type'] ) ) . '_field_ids';
		/* Search handler */
		if ( isset( $_GET['s'] ) && '' != trim( $_GET['s'] ) ) {
			/* Sanitize search query */
			$searchrequest = sanitize_text_field( stripslashes( $_GET['s'] ) );
			$like = '%' . $wpdb->esc_like( $searchrequest ) . '%';
			$searchrequest = 'AND ' . $table_fields_id . '.`field_name` LIKE "' . $like . '"';
            $searchrequest = stripslashes( trim ( $searchrequest ));
		}
		/* Sort function */
		if ( isset( $_GET['orderby'] ) && isset( $_GET['order'] ) ) {
			/* Check permitted names of field */
			switch ( $_GET['orderby'] ) {
				case 'title':
					$orderrequest = 'ORDER BY ' . $table_fields_id . '.`field_name` ' . sanitize_text_field( stripslashes( $_GET['order'] ) );
					break;
				case 'type':
					$orderrequest = 'ORDER BY ' . $table_fields_id . '.`field_type_id` ' . sanitize_text_field( stripslashes( $_GET['order'] ) );
					break;
				case 'slug':
					$orderrequest = 'ORDER BY ' . $table_fields_id . '.`field_slug` ' . sanitize_text_field( stripslashes( $_GET['order'] ) );
					break;
				default:
					$orderrequest = 'ORDER BY ' . $table_fields_id . '.`field_id` ASC';
					break;
			}
            $orderrequest = stripslashes( trim( $orderrequest ) );
		}
		/* Get the value of number of field on one page */
		$perpage = $this->get_items_per_page( 'fields_per_page', 20 );
		$current_page = $this->get_pagenum();
		$limit_range_start = ( $current_page - 1 ) * $perpage;

		$query = $wpdb->prepare( "SELECT * FROM {$table_fields_id} WHERE `visible_status` = 1 {$searchrequest} {$orderrequest} LIMIT %d, %d", $limit_range_start, $perpage);
		$fields_query_result = $wpdb->get_results( $query, ARRAY_A );
		return $fields_query_result;
	}

	/**
	 * Displays the list of registered attributes
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
				<?php echo esc_html( $title );
                $post_type = sanitize_text_field( stripslashes( $_GET['post_type'] ) );
                $page = sanitize_text_field( stripslashes( $_GET['page'] ) ) ?>
				<a href="<?php echo esc_url( admin_url( "edit.php?post_type={$post_type}&amp;page={$page}&amp;action=add_new" ) ) ?>" class="page-title-action add-new-h2" >
                    <?php _e( 'Add New', BWS_BKNG_TEXT_DOMAIN ); ?>
                </a>
			</h1>
			<?php $this->show_notices(); ?>
			<form id="bkng_attributes_form" method="post">
				<?php $this->display(); ?>
			</form>
		</div>
	<?php }

	public function display_page () {
        if( isset( $_GET['action'] ) ) {
			$this->display_action_page();
		} else {
			$this->display_table();
		}
	}

	/**
	 * Displays the list of registered attributes
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function display_action_page() {
		global $title, $bws_bkng, $wpdb;

		if ( isset( $_GET['action'] ) ) {
            $name_of_page = '';
			if ( isset( $_REQUEST['field_id'] ) ){
				$name_of_page = __( 'Edit Attribute', BWS_BKNG_TEXT_DOMAIN );
				$field_id = isset( $_REQUEST['field_id'] ) ? absint( $_REQUEST['field_id'] ) : NULL;
				$post_type = sanitize_text_field( stripslashes( $_GET['post_type'] ) );
				$field_options = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_ids` WHERE `field_id`=%d', $field_id ), ARRAY_A );
				if ( ! $field_options ) {
					/* If entry not exist - create new entry */
					$field_id			= NULL;
				} else {
					$field_name				= $field_options['field_name'];
					$description			= $field_options['description'];
					$field_slug				= $field_options['field_slug'];
					$field_type_id		    = $field_options['field_type_id'];

					/* Get available values to checkbox, radiobutton, select, etc */
					if ( '10' == $field_type_id ) {
						$field_pattern = $wpdb->get_var( $wpdb->prepare( 'SELECT `value_name` FROM `' . BWS_BKNG_DB_PREFIX . sanitize_text_field( stripslashes( $_GET['post_type'] ) ) . '_field_values` WHERE `field_id`=%d', $field_id ) );
					} elseif ( '6' == $field_type_id ) {
						$field_date_format = $wpdb->get_var( $wpdb->prepare( 'SELECT `value_name` FROM `' . BWS_BKNG_DB_PREFIX . sanitize_text_field( stripslashes( $_GET['post_type'] ) ) . '_field_values` WHERE `field_id`=%d', $field_id ) );
					} elseif ( '7' == $field_type_id ) {
						$field_time_format = $wpdb->get_var( $wpdb->prepare( 'SELECT `value_name` FROM `' . BWS_BKNG_DB_PREFIX . sanitize_text_field( stripslashes( $_GET['post_type'] ) ) . '_field_values` WHERE `field_id`=%d', $field_id ) );
					} elseif ( '8' == $field_type_id ) {
						$date_and_time = unserialize( $wpdb->get_var( $wpdb->prepare( 'SELECT `value_name` FROM `' . BWS_BKNG_DB_PREFIX . sanitize_text_field( stripslashes( $_GET['post_type'] ) ) . '_field_values` WHERE `field_id`=%d', $field_id ) ) );
						if ( isset( $date_and_time['date'] ) ) {
							$field_date_format = $date_and_time['date'];
						}
						if ( isset( $date_and_time['time'] ) ) {
							$field_time_format = $date_and_time['time'];
						}
					} elseif ( '1' == $field_type_id || '9' == $field_type_id ) {
						$field_maxlength = $wpdb->get_var( $wpdb->prepare( 'SELECT `value_name` FROM `' . BWS_BKNG_DB_PREFIX . sanitize_text_field( stripslashes( $_GET['post_type'] ) ) . '_field_values` WHERE `field_id`=%d', $field_id ) );
					} elseif ( '2' == $field_type_id ) {
						$unser_textarea = maybe_unserialize( $wpdb->get_var( $wpdb->prepare( 'SELECT `value_name` FROM `' . BWS_BKNG_DB_PREFIX . sanitize_text_field( stripslashes( $_GET['post_type'] ) ) . '_field_values` WHERE `field_id`=%d', $field_id ) ) );
						$field_rows = $unser_textarea['rows'];
						$field_cols = $unser_textarea['cols'];
						$field_maxlength = $unser_textarea['max_length'];
					} else {
						$available_values = $wpdb->get_results( $wpdb->prepare( 'SELECT `value_id`, `value_name`, `order` FROM `' . BWS_BKNG_DB_PREFIX . sanitize_text_field( stripslashes( $_GET['post_type'] ) ) . '_field_values` WHERE `field_id`=%d ORDER BY `order`', $field_id ), ARRAY_A );
					}
				}
			} elseif ( isset( $_GET['action'] ) && 'add_new' == $_GET['action'] ) {
				$name_of_page = __( 'Add New Attribute', BWS_BKNG_TEXT_DOMAIN );
				$field_id			= NULL;
				$available_values = array();
				$field_time_format = $field_date_format ='';
			}

			$field_type_ids = $bws_bkng->data_loader->get_field_type_id();

			/* If field id is NULL - create new entry */
			if ( is_null( $field_id ) ) {
				if ( ! $field_id = $wpdb->get_var( 'SELECT MAX(`field_id`) FROM `' . BWS_BKNG_DB_PREFIX . sanitize_text_field( stripslashes( $_GET['post_type'] ) ) . '_field_ids`' ) ) {
					/* If table is empty */
					$field_id = 1;
				} else {
					/* Generate new id */
					$field_id++;
				}
			}
            $post_type = sanitize_text_field( stripslashes( $_GET['post_type'] ) );
            $page = $bws_bkng->plugin_prefix . '_' . sanitize_text_field( stripslashes( $_GET['post_type'] ) );
			$action = admin_url( "edit.php?post_type={$post_type}&page={$page}_attributes&field_id={$field_id}&action=edit" ); ?>
			<div class="wrap">
				<h1>
					<?php echo esc_html( $name_of_page ); ?>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . sanitize_text_field( stripslashes( $_GET['post_type'] ) ) . '&amp;page=' . sanitize_text_field( stripslashes( $_GET['page'] ) ) . '&amp;action=add_new' ) ) ?>" class="page-title-action add-new-h2" >
                        <?php _e( 'Add New', BWS_BKNG_TEXT_DOMAIN ); ?>
                    </a>
				</h1>
				<?php $this->show_notices(); ?>
				<form class="bws_form" method="post" action="<?php echo esc_url( $action ); ?>">
					<table class="form-table">
						<tbody>
							<tr>
								<th><?php _e( 'Name', BWS_BKNG_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="text" name="bws_bkng_field_name" value="<?php echo isset( $field_name) ? esc_attr( $field_name ) : ''; ?>" />
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Slug', BWS_BKNG_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="text" name="bws_bkng_field_slug" value="<?php echo isset( $field_slug) ? esc_attr( $field_slug ) : ''; ?>" />
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Type', BWS_BKNG_TEXT_DOMAIN ); ?></th>
								<td>
									<select id="bws_bkng-select-type" name="bws_bkng_field_type_id">
										<?php foreach ( $field_type_ids as $id => $field_name ) { /* Create select with field types */ ?>
											<option value="<?php echo esc_attr( $id ); ?>"<?php if( isset( $field_type_id ) ) selected( $field_type_id, $id ); ?>><?php echo esc_attr( $field_name ); ?></option>
										<?php } ?>
									</select>
								</td>
							</tr>
							<tr class="bws_bkng-maxlength">
								<th><?php _e( 'Max Length', BWS_BKNG_TEXT_DOMAIN ); ?></th>
								<td>
									<input type="number" min="1" name="bws_bkng_maxlength" value="<?php echo isset( $field_maxlength ) ? esc_attr( $field_maxlength ) : ''; ?>" />
									<div class="bws_info"><?php _e( 'Specify field max length (for text field and textarea type) or max number (for number field type).', BWS_BKNG_TEXT_DOMAIN ); ?></div>
								</td>
							</tr>
							<tr class="bws_bkng-rows">
									<th><?php _e( 'Field width in characters', BWS_BKNG_TEXT_DOMAIN ); ?></th>
									<td>
											<input type="number" min="1" name="bws_bkng_rows" value="<?php echo isset( $field_rows) ? esc_attr( $field_rows ) : ''; ?>" />
									</td>
							</tr>
							<tr class="bws_bkng-cols">
									<th><?php _e( 'The height of the field in the text lines', BWS_BKNG_TEXT_DOMAIN ); ?></th>
									<td>
											<input type="number" min="1" name="bws_bkng_cols" value="<?php echo isset( $field_cols) ? esc_attr( $field_cols ) : ''; ?>" />
									</td>
							</tr>
							<tr class="bws_bkng-date-format">
								<th scope="row"><?php _e( 'Date Format', BWS_BKNG_TEXT_DOMAIN ) ?></th>
								<td>
									<fieldset>
										<legend class="screen-reader-text"><span><?php _e( 'Date Format', BWS_BKNG_TEXT_DOMAIN ) ?></span></legend>
										<?php $date_formats = array_unique( apply_filters( 'date_formats', array( 'F j, Y', 'Y-m-d', 'm/d/Y', 'd/m/Y' ) ) );
										$custom = true;
										foreach ( $date_formats as $format ) {
											echo "\t<label title='" . esc_attr( $format ) . "'><input type='radio' name='bws_bkng_date_format' value='" . esc_attr( $format ) . "'";
											if ( isset( $field_date_format ) && $field_date_format == $format ) {
												echo " checked='checked'";
												$custom = false;
											}
											echo ' /> ' . date_i18n( $format ) . "</label><br />\n";
										}
										echo '	<label><input type="radio" name="bws_bkng_date_format" id="bws_bkng_date_format_custom_radio" value="custom"';
										checked( $custom );
										echo '/> ' . __( 'Custom:', BWS_BKNG_TEXT_DOMAIN ) . '<span class="screen-reader-text"> ' . __( 'enter a custom date format in the following field', BWS_BKNG_TEXT_DOMAIN ) . "</span></label>\n";
										echo '<label for="bws_bkng_date_format_custom" class="screen-reader-text">' . __( 'Custom date format:', BWS_BKNG_TEXT_DOMAIN ) . '</label><input type="text" name="bws_bkng_date_format_custom" id="bws_bkng_date_format_custom" value="' . ( isset( $field_date_format ) && ! empty( $field_date_format ) ? esc_attr( $field_date_format ) : '' ) . '" class="small-text" />
										<span class="screen-reader-text">' . __( 'example:', BWS_BKNG_TEXT_DOMAIN ) . ' </span><span class="example"> ' . ( isset( $field_date_format ) && ! empty( $field_date_format ) ? date_i18n( $field_date_format ) : '' ) . "</span> <span class='spinner'></span>\n"; ?>
										<p><a target="_blank" href="https://codex.wordpress.org/Formatting_Date_and_Time"><?php _e( 'Documentation on date and time formatting.', BWS_BKNG_TEXT_DOMAIN ); ?></a></p>
									</fieldset>
								</td>
							</tr>
							<tr class="bws_bkng-time-format">
								<th scope="row"><?php _e( 'Time Format', BWS_BKNG_TEXT_DOMAIN ); ?></th>
								<td>
									<fieldset>
										<legend class="screen-reader-text"><span><?php _e( 'Time Format', BWS_BKNG_TEXT_DOMAIN ); ?></span></legend>
										<?php $time_formats = array_unique( apply_filters( 'time_formats', array( 'g:i a', 'g:i A', 'H:i' ) ) );
										$custom = true;
										foreach ( $time_formats as $format ) {
											echo "\t<label title='" . esc_attr( $format ) . "'><input type='radio' name='bws_bkng_time_format' value='" . esc_attr( $format ) . "'";
											if ( isset( $field_time_format ) && $field_time_format == $format ) {
												echo " checked='checked'";
												$custom = false;
											}
											echo ' /> ' . date_i18n( $format ) . "</label><br />\n";
										}
										echo '	<label><input type="radio" name="bws_bkng_time_format" id="bws_bkng_time_format_custom_radio" value="custom"';
										checked( $custom );
										echo '/> ' . __( 'Custom:', BWS_BKNG_TEXT_DOMAIN ) . '<span class="screen-reader-text"> ' . __( 'enter a custom time format in the following field', BWS_BKNG_TEXT_DOMAIN ) . "</span></label>\n";
										echo '<label for="bws_bkng_time_format_custom" class="screen-reader-text">' . __( 'Custom time format:', BWS_BKNG_TEXT_DOMAIN ) . '</label><input type="text" name="bws_bkng_time_format_custom" id="bws_bkng_time_format_custom" value="' . ( isset( $field_time_format ) && ! empty( $field_time_format ) ? esc_attr( $field_time_format ) : '' ) . '" class="small-text" /> <span class="screen-reader-text">' . __( 'example:', BWS_BKNG_TEXT_DOMAIN ) . ' </span><span class="example"> ' . ( isset( $field_time_format ) && ! empty( $field_time_format ) ? date_i18n( $field_time_format ) : '' ) . "</span> <span class='spinner'></span>\n"; ?>
										<p><a target="_blank" href="https://codex.wordpress.org/Formatting_Date_and_Time"><?php _e( 'Documentation on date and time formatting.', BWS_BKNG_TEXT_DOMAIN ); ?></a></p>
									</fieldset>
								</td>
							</tr>
							<tr class="bws_bkng-fields-container">
								<th><?php _e( 'Available Values', BWS_BKNG_TEXT_DOMAIN ); ?></th>
								<td>
									<div class="bws_info hide-if-js">
										<div class="bws_bkng-value-name">
											<?php _e( 'Name of value', BWS_BKNG_TEXT_DOMAIN ); ?>
										</div>
										<div class="bws_bkng-delete">
											<?php _e( 'Delete', BWS_BKNG_TEXT_DOMAIN ); ?>
										</div>
									</div><!--.bws_bkng-values-info-->
									<div class="bws_bkng-drag-values-container">
										<?php if ( isset( $available_values ) ) {
											for ( $i = 0; $i < sizeof( $available_values ); $i++ ) { ?>
											<div class="bws_bkng-drag-values">
												<input type="hidden" name="bws_bkng_value_id[]" value="<?php if ( ! empty( $available_values[ $i ]['value_id'] ) ) echo esc_attr( $available_values[ $i ]['value_id'] ); ?>" />
												<img class="bws_bkng-drag-field hide-if-no-js bws_bkng-hide-if-is-mobile" title="" src="<?php echo esc_url( plugins_url( 'images/dragging-arrow.png', BWS_BKNG_PATH . '/' . BWS_BKNG_FOLDER ) ); ?>" alt="drag-arrow" />
												<input placeholder="<?php _e( 'Name of value', BWS_BKNG_TEXT_DOMAIN ); ?>" class="bws_bkng-add-options-input" type="text" name="bws_bkng_available_values[<?php echo esc_attr( $available_values[ $i ]['value_id'] ); ?>]" value="<?php echo esc_attr( $available_values[ $i ]['value_name'] ); ?>" />
												<span class="bws_bkng-value-delete"><input type="checkbox" name="bws_bkng-value-delete[]" value="<?php if ( ! empty( $available_values[ $i ]['value_id'] ) ) echo esc_attr( $available_values[ $i ]['value_id'] ); ?>" /><label></label></span>
											</div><!--.bws_bkng-drag-values-->
										<?php
											}
										}
										?>
										<div class="bws_bkng-drag-values <?php if ( ! empty( $available_values ) ) echo 'hide-if-js'; ?>">
											<input type="hidden" name="bws_bkng_value_id[]" value="" />
											<img class="bws_bkng-drag-field hide-if-no-js bws_bkng-hide-if-is-mobile" title="" src="<?php echo esc_url( plugins_url( 'images/dragging-arrow.png', BWS_BKNG_PATH . '/' . BWS_BKNG_FOLDER ) ); ?>" alt="drag-arrow" />
											<input placeholder="<?php _e( 'Name of value', BWS_BKNG_TEXT_DOMAIN ); ?>" class="bws_bkng-add-options-input" type="text" name="bws_bkng_available_values[]" value="" />
											<span class="bws_bkng-value-delete"><input type="checkbox" name="bws_bkng-value-delete[]" value="" /><label></label></span>
										</div><!--.bws_bkng-drag-values-->
									</div><!--.bws_bkng-drag-values-container-->
									<div class="bws_bkng-add-button-container">
										<input type="button" class="button-small button bws_bkng-small-button hide-if-no-js" id="bws_bkng-add-field" name="bws_bkng-add-field" value="<?php _e( 'Add', BWS_BKNG_TEXT_DOMAIN ); ?>" />
										<p class="hide-if-js"><?php _e( 'Click save button to add more values', BWS_BKNG_TEXT_DOMAIN ); ?></p>
									</div>
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Description', BWS_BKNG_TEXT_DOMAIN ); ?></th>
								<td>
									<textarea class="bws_bkng-description" name="bws_bkng_description"><?php echo isset( $description ) ? esc_textarea( $description ) : ''; ?></textarea>
								</td>
							</tr>
						</tbody>
					</table>
					<p class="submit">
						<input type="hidden" name="bws_bkng_save_field" value="true" />
						<input type="hidden" name="bws_bkng_field_id" value="<?php echo esc_attr( $field_id ); ?>" />
						<input id="bws-submit-button" type="submit" class="button-primary" name="bws_bkng_save_settings" value="<?php _e( 'Save Changes', BWS_BKNG_TEXT_DOMAIN ); ?>" />
						<?php wp_nonce_field( 'bws_bkng_nonce_name' ); ?>
					</p>
				</form>
			</div>
		<?php }
	}

	/**
	 * Updates the attribute data in database
	 * @since  0.1
	 * @access private
	 * @param  string    $old_slug   The old attribute slug
	 * @param  string    $new_slug   The new attribute slug
	 * @param  array     $data       The attribute data
	 * @return void
	 */
	private function update_attribute( $old_slug, $new_slug, $data ) {
		global $bws_bkng, $wpdb;

		/* if they edited the taxonomy */
		if ( $bws_bkng->is_taxonomy( $this->attributes[ $old_slug ]['meta_type'] ) ) {
			/* if the edited attribute is still a taxonomy */
			if ( $bws_bkng->is_taxonomy( $this->attributes[ $new_slug ]['meta_type'] ) ) {
				/* !!! Do not join this "if"-statement with the previous */
				if ( $old_slug != $new_slug ) {
					$wpdb->update(
						"{$wpdb->prefix}term_taxonomy",
						array( "taxonomy" => $new_slug ),
						array( "taxonomy" => $old_slug )
					);
					unset( $this->attributes[ $old_slug ] );
				}
			} else {
				$this->remove_attribute( $old_slug );
			}
		/* if they edit the metafield */
		} else {
			/* if the edited attribute is a taxonomy */
			if ( $bws_bkng->is_taxonomy( $this->attributes[ $new_slug ]['meta_type'] ) ) {
				$this->remove_attribute( $old_slug );
			} elseif ( $old_slug != $new_slug ) {
				$wpdb->update(
					"{$wpdb->prefix}postmeta",
					array(
						"meta_key"   => $new_slug,
						"meta_value" => ''
					),
					array( "meta_key" => $old_slug )
				);
			}
		}
	}

	/**
	 * Completely removes the attribute from database
	 * @since  0.1
	 * @access private
	 * @param  string     $slug      The attribute slug
	 * @return array
	 */
	private function remove_attribute( $slug ) {
		global $bws_bkng, $wpdb;

		if ( ! $this->attributes[ $slug ]['removable'] )
			return false;

		$slug = esc_sql( $slug );

		/* remove all terms for the given taxonomy */
		if ( $bws_bkng->is_taxonomy( $this->attributes[ $slug ]['meta_type'] ) ) {

			$terms = $bws_bkng->get_terms( $slug, array( 'hide_empty' => false ) );

			if ( empty( $terms ) )
				return;

			$term_ids = array();

			foreach( $terms as $term ) {
				wp_delete_term( $term->term_id, $slug );
				$term_ids[] = $term->term_id;
			}

			/* remove dependencies with products categories */
			$table = BWS_BKNG_DB_PREFIX . 'cat_att_dependencies';
			$wpdb->query( "DELETE FROM `{$table}` WHERE `attribute_slug` LIKE '{$slug}';" );

		/* remove all metafields */
		} else {
			/**
			 * Eventhough there is specified that it is needed to delete all data for the product with the ID 1000 only, there will be removed meta fields for all products
			 * @see https://developer.wordpress.org/reference/functions/delete_metadata/
			 */
			delete_metadata( BWS_BKNG_POST, 1000, $slug, '', true );
		}

		return true;
	}

	/**
	 * Prepares the attribute data before saving it to database
	 * @since  0.1
	 * @access private
	 * @param  array          $data   The attribute data
	 * @return array
	 */
	private function esc_attr_data( $data ) {
		global $bws_bkng;

		if ( empty( $data ) )
			$this->add_error( 'empty_data', __( 'Not enough data', BWS_BKNG_TEXT_DOMAIN ) . '.' );

		$is_new_attribute = empty( $_GET['slug'] );

		if( $is_new_attribute ) {
			$is_editable = true;
		} else {
			$old_data    = $this->attributes[ sanitize_text_field( stripslashes( $_GET['slug'] ) ) ];
			$is_editable = ! isset( $old_data['editable'] ) || $old_data['editable'];
		}

		$data['label']       = empty( $data['label'] ) ? '' : esc_html( trim( $data['label'] ) );
		$data['description'] = empty( $data['description'] ) ? '' : esc_html( trim( $data['description'] ) );
		$data['categories']  = empty( $data['categories'] ) ? array() : array_filter( array_map( 'absint', (array)$data['categories'] ) );

		if ( $is_editable ) {

			$data['slug'] = empty( $data['slug'] ) ? $this->esc_slug( $data['label'] ) : $this->esc_slug( $data['slug'] );

			if ( empty( $data['slug'] ) )
				$this->add_error( 'slug', __( 'Attribute slug is wrong or empty', BWS_BKNG_TEXT_DOMAIN ) . '.' );
			elseif ( $bws_bkng->is_taxonomy( $data['meta_type'] ) && taxonomy_exists( $data['slug'] ) )
				$this->add_error( 'slug', __( 'Taxonomy with such slug already exists', BWS_BKNG_TEXT_DOMAIN ) . '.' );
			else
				$data['slug'] = "bkng_{$data['slug']}";

			$types                = array_keys( $bws_bkng->get_meta_types() );
			$data['meta_type']    = empty( $data['meta_type'] ) || ! in_array( $data['meta_type'], $types ) ? 'text' : $data['meta_type'];
			$data['editable']     = true;
		} else {
			$data['slug']         = $this->esc_slug( $_GET['slug'] );
			$data['meta_type']    = $old_data['meta_type'];
			$data['editable']     = false;
		}

		$data['removable'] = true;

		$old = empty( $data['meta_options'] ) ? array() : (array)$data['meta_options'];
		$new = array();
		switch ( $data['meta_type'] ) {
			case 'number':
				$new['number_measure']  = empty( $old['number_measure'] ) ? '' : esc_html( trim( $old['number_measure'] ) );
				$new['number_decimals'] = empty( $old['number_decimals'] ) ? 0 : esc_html( trim( $old['number_decimals'] ) );
				break;
			case 'select_locations':
			case 'select_checkboxes':
			case 'select_radio':
			case 'select':
				$data['show_in_menu']      = false;
				$data['meta_box_cb']       = false;
				$data['show_ui']           = true;
				$data['show_tagcloud']     = false;
				$data['show_admin_column'] = false;
				$data['query_var']         = false;
				$data['hierarchical']      = false;
				break;
			default:
				break;
		}
		$data['meta_options'] = $new;

		return $data;
	}

	/**
	 * Sanitizes the attribute slug
	 * @since  0.1
	 * @access private
	 * @param  string     $slug
	 * @return void
	 */
	private function esc_slug( $slug ) {
		$slug   = sanitize_title( $slug );
		$strlen = strlen( $slug );
		return 1 < $strlen && 27 > $strlen && preg_match( "/^[a-zA-Z0-9]+[a-zA-Z0-9-_]*[a-zA-Z0-9]+$/", $slug ) && ! is_numeric( $slug ) ? $slug : '';
	}

	/**
	 * Sanitizes the attribute slug
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
								"One attribute deleted",
								"%s attributes deleted",
								$formated,
								BWS_BKNG_TEXT_DOMAIN
							),
							$count
						);
						break;
					case 'save':
						$this->message = __( "Attribute updated", BWS_BKNG_TEXT_DOMAIN );
						break;
					default:
						break;
				}
			}
		}

		if ( ! empty( $this->message ) ) { ?>
			<div class="updated fade inline bws_visible"><p><?php echo esc_html( $this->message ); ?>.</p></div>
		<?php }
	}
}
