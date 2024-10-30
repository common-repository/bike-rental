<?php
/**
 * Handle the content of "Settings" tab of user profile
 * @since  Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Profile_History' ) )
	return;

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class BWS_BKNG_Profile_History extends WP_List_Table {

	/**
	 * Contains the status slug by which the orders list is filtered
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $filter_status;

	/**
	 * Contains the field by which the orders list is ordered
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $order_by;

	/**
	 * Contains the order direction (asc, desc) by which the orders list is ordered
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $order;

	/**
	 * Contains the number of displayed orders per page
	 * @since  0.1
	 * @access private
	 * @var    int
	 */
	private $per_page;


	/**
	 * Contains the number currently displayed orders page
	 * @since  0.1
	 * @access private
	 * @var    int
	 */
	private $paged;

	/**
	 * Contains the value of the search query
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $s;

	/**
	 * Contains the prefix of database tables where ordered products data are kept
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $db_table;
	
	/**
	 * Contains the post type of current page
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $post_type;

	/**
	 * Contains the name of the action nonce
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $nonce = 'bkng_ordered_list_nonce';

	/**
	 * Class constructor
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function __construct() {
		$this->post_type = sanitize_text_field( stripslashes( $_REQUEST['post_type'] ) );

		$this->db_table = BWS_BKNG_DB_PREFIX . $this->post_type . '_orders';

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		/* Set parent defaults */
		parent::__construct( array(
			'singular' => __( 'order', BWS_BKNG_TEXT_DOMAIN ),
			'plural'   => __( 'orders', BWS_BKNG_TEXT_DOMAIN ),
			'ajax'     => true
		) );
	}

	/**
	 * Show message if item list is empty
	 * @since	0.1
	 * @access   public
	 * @param  void
	 * @return void
	 */
	public function no_items() { ?>
		<p><?php _e( 'No products found', BWS_BKNG_TEXT_DOMAIN ); ?></p>
	<?php }

	/**
	 * Get the list of table columns.
	 * @since	0.1
	 * @access   public
	 * @see WP_List_Table::single_row_columns()
	 * @param  void
	 * @return array	 An associative array containing column information: 'slugs'=>'Visible Titles'
	 */
	public function get_columns() {
		$columns = array(
			'cb'       	=> '<input type="checkbox" />',
			'order'  	=> __( 'Order', BWS_BKNG_TEXT_DOMAIN ),
			'count'		=> __( 'Amount of Products', BWS_BKNG_TEXT_DOMAIN ),
			'status'   	=> __( 'Status', BWS_BKNG_TEXT_DOMAIN ),
			'total'    	=> __( 'Total', BWS_BKNG_TEXT_DOMAIN ),
			'date'     	=> __( 'Date', BWS_BKNG_TEXT_DOMAIN )
		);
		return $columns;
	}

	 /**
	 * Get the list of sortable columns.
	 * @since	0.1
	 * @access   public
	 * @param  void
	 * @return array   An associative array containing all the columns
	 *				   that should be sortable: 'slugs'=>array('data_values',bool)
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'order' => array( 'order_id', false ),
			'total' => array( 'total', false ),
			'date'  => array( 'date_create', false )
		);
		return $sortable_columns;
	}

	/**
	 * @see WP_List_Table::single_row_columns()
	 * @since	0.1
	 * @access   public
	 *
	 * @param  array   $item		  A singular item (one full row's worth of data)
	 * @param  array   $column_name   The name/slug of the column to be processed
	 * @return string				  Text or HTML to be placed inside the column <td>
	 */
	public function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'cb':
			case 'status':
			case 'order':
			case 'count':
			case 'total':
			case 'date':
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}

	/**
	 * Manage content of column with checboxes
	 * @since	0.1
	 * @access   public
	 * @param   array	$item	  The current item data.
	 * @return  string			  with the column content
	 */
	public function column_cb( $item ) {
		return '<input type="checkbox" name="bkng_order_id[]" value="' . $item['id'] . '" />';
	}

	public function column_order( $item ) {
		$link       = '<a href="%1$s" data-order-id="' . $item['order_id'] . '" data-action="view">%2$s</a>';
		$action_url = add_query_arg( array( 'action' => 'view', 'bkng_order_id' => $item['order_id'] ) );
		$actions = array(
			'view'   => sprintf( $link, $action_url, __( 'View', BWS_BKNG_TEXT_DOMAIN ) ),
		);

		$order_title = '<strong>' . sprintf( $link, $action_url, '#' . $item['order_id'] ) . '</strong>';

		return  $order_title . $this->row_actions( $actions );
	}

	public function column_total( $item ) {
		return ( ! bws_bkng_show_product_price( $item['order_id'] ) ? '<span class="bkng_info_icon dashicons dashicons-info" title="' . __( 'Price on Request', BWS_BKNG_TEXT_DOMAIN ) .'"></span>' : '' ) . $item['total'];
	}

	/**
	 * Add necessary class
	 * @since	0.1
	 * @access   public
	 * @param     array     $item        The cuurrent link data.
	 * @return    void
	 */
	public function single_row( $item ) {
		echo '<tr class="bkng_order_id_' . esc_attr( $item['order_id'] ) . '" />';
			$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Show either short or full order details
	 * @since	0.1
	 * @access   public
	 * @param     array     $item        The cuurrent link data.
	 * @return    void
	 */
	public function display_rows() {
		foreach ( $this->items as $item ) {
			$is_opened_row = isset( $_GET['action'], $_GET['bkng_order_id'] ) && 'view' == $_GET['action'] && $item['order_id'] == $_GET['bkng_order_id'];
			if ( $is_opened_row ) {
				$data = $this->get_products_by_order( absint( $_GET['bkng_order_id'] ), true );
				$this->get_opened_order( $data );
			} else {
				$this->single_row( $item );
			}
		}
	}

	/**
	 * Add action links before and after list of messages
	 * @since	0.1
	 * @access   public
	 * @param  void
	 * @return string   list of action links
	 */
	public function get_views() {
		global $wpdb, $bws_bkng;
		
		$links = array();
		$statuses = $bws_bkng->get_order_statuses();

		$query =
			"SELECT COUNT(`id`) AS `all`, %s
			FROM `" . $this->db_table . "`
			WHERE `user_id` = '" . get_current_user_id() . "';";
		$query_parts = $filters = array();

		foreach ( array_keys( $statuses ) as $status ) {
			$status = esc_sql( $status );
			$query_parts[] = "( SELECT COUNT(`id`) FROM `{$this->db_table}` WHERE `status`='{$status}' ) AS `{$status}`";
		}

		$counts = $wpdb->get_results( sprintf( $query, implode( ',', $query_parts ) ) );

		if ( empty( $counts[0] ) || $wpdb->last_error ) {
			return '';
		}

		$statuses['all'] = __( 'All', BWS_BKNG_TEXT_DOMAIN );

		foreach ( $counts[0] as $key => $value ) {
			$class = $key == $this->filter_status ? ' class="current"' : '';
			$url   = add_query_arg( 'bkng_filter_status', $key );
			$filters[ $key ] = '<a href="' . $url . '"' . $class . '>' . $statuses[ $key ] . '&nbsp;<span class="count">(' . $value . ')</span></a>';
		}
		return $filters;
	}

	/**
	 * Generate the table navigation above or below the table.
	 * This function was addedd in order to overwrite nonce fields from
	 * the parent class.
	 * @since  0.1
	 * @access public
	 * @param  string $which
	 * @return void
	 */
	public function display_tablenav( $which ) {

		if ( 'top' === $which ) {
			wp_nonce_field( $this->nonce, '_wpnonce', 0 );
		}
		?>

		<div class="tablenav <?php echo esc_attr( $which ); ?>">
		
			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>

			<br class="clear" />
		</div>
	<?php }

	/**
	 * Handle ajax request
	 * @see BWS_BKNG_AJAX::handle_profile_ajax()
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function ajax_response() {
		check_ajax_referer( $this->nonce, 'bkng_nonce' );
		
		$data = $this->get_products_by_order( absint( $_POST['bkng_order_id'] ), true );
		$this->get_opened_order( $data );
	}

	/**
	 * Displays full order details
	 * @since  0.1
	 * @access public
	 * @param  array	$order
	 * @return void
	 */
	public function get_opened_order( $order ) {
		global $bws_bkng;

		$date_format = get_option( 'date_format' );
		$statuses = $bws_bkng->get_order_statuses();
		$status = $statuses[ $order['status'] ];

		$main_product_key = key( $order['products'] );

		foreach ( $order['products'] as $key => $product ) {
			?>
			<tr class="inline-edit-row bkng-opened-order <?php echo 'bkng_order_id_' . esc_attr( $order['id'] ); ?>">
				<td colspan="2" class="colspanchange">
					<legend class="inline-edit-legend"><?php echo esc_html( $product['title'] ); ?></legend>
					<?php
					if ( $main_product_key === $key ) {
						echo $this->get_featured_image( $key, array( 150, 150 ) );
					 }
					 ?>
				</td>
				<td>
					<div class="bkng-grid-table">
						<?php
						if ( $main_product_key === $key && isset( $order['meta'] ) ) {
							foreach ( $order['meta'] as $name => $value ) {
								?>
								<span><?php echo esc_html( $name ); ?>:</span>
								<?php
								echo esc_html( $value );
							}
						} else {
							?>
							<span><?php _e( 'Quantity:', BWS_BKNG_TEXT_DOMAIN ); ?></span>
							<?php echo esc_html( $product['quantity'] ); ?>

							<span><?php _e( 'Price:', BWS_BKNG_TEXT_DOMAIN ); ?></span>
							<div><?php echo bws_bkng_price_format( $product['price'] ); ?></div>
						<?php } ?>
					</div>
				</td>
				<td>
					<span><?php echo esc_html( $status ); ?></span>
				</td>
				<td>
					<span><?php echo bws_bkng_price_format( $product['total'] ); ?></span>
				</td>
				<td>
					<?php echo date_i18n( $date_format, $product['rent_interval']['from'] ); ?>
					<span>&ndash;</span>
					<?php echo date_i18n( $date_format, $product['rent_interval']['till'] ); ?>
				</td>
			</tr>
		<?php } ?>
		<tr class="inline-edit-row bkng-opened-order <?php echo 'bkng_order_id_' . esc_attr( $order['id'] ); ?>">
			<td colspan="<?php echo count( $this->get_columns() ) ?>" class="colspanchange">
				<fieldset>
					<div class="bkng-grid-table right">
						<span><?php _e( 'Total:', BWS_BKNG_TEXT_DOMAIN ); ?></span>
						<div><?php echo bws_bkng_price_format( $order['total'] ); ?></div>
					</div>
					<div class="submit">
						<a href="<?php echo esc_url( remove_query_arg( array( 'action', 'bkng_order_id' ) ) ); ?>">
							<button type="button" data-action="close" data-order-id="<?php echo esc_attr( $order['id'] ); ?>" class="button alignleft"><?php _e( 'Close', BWS_BKNG_TEXT_DOMAIN ); ?></button>
						</a>
						<a href="<?php echo esc_url( get_post_permalink( $main_product_key ) ); ?>">
							<button type="button" class="button button-primary alignright"><?php _e( 'Rent again', BWS_BKNG_TEXT_DOMAIN ); ?></button>
						</a>
						<br class="clear">
					</div>
				</fieldset>
			</td>
		</tr>
		<?php
	}

	public function get_featured_image( $id, $size = array( 65, 65 ) ) {
		$thumb = get_the_post_thumbnail( $id, $size );
		return empty( $thumb ) ? '<img width="' . $size[0] . '" height="' . $size[1] . '" class="bkng-obj-cover" src="' . get_default_image_src() . '" />' : $thumb;
	}

	/**
	 * Prepare necessary data before displaying
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function prepare_items() {
		global $bws_bkng, $wpdb;

		$sortable_columns = $this->get_sortable_columns();

		$this->filter_status = isset( $_REQUEST['bkng_filter_status'] ) && in_array( $_REQUEST['bkng_filter_status'], array_keys( $bws_bkng->get_order_statuses() ) ) ? sanitize_text_field( stripslashes( $_REQUEST['bkng_filter_status'] ) ) : 'all';

		$this->order_by 		= isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $sortable_columns ) ) ? $sortable_columns[ $_REQUEST['orderby'] ][0] : 'id';
		$this->order    		= isset( $_REQUEST['order'] ) && in_array( strtoupper( $_REQUEST['order'] ), array( 'ASC', 'DESC' ) ) ? $_REQUEST['order'] : 'desc';
		$this->paged    		= isset( $_REQUEST['paged'] ) && is_numeric( $_REQUEST['paged'] ) ? (int)$_REQUEST['paged'] - 1 : 0;
		$this->s        		= isset( $_REQUEST['s'] ) ? sanitize_text_field( stripslashes( $_REQUEST['s'] ) ) : '';
		$this->per_page 		= $this->get_items_per_page( 'bkng_per_page', 20 );
		$this->_column_headers 	= array( $this->get_columns(), array(), $sortable_columns );
		$this->items           	= $this->get_orders();

		$total_items 			= count( $this->items );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $this->per_page,
			'total_pages' => ceil( $total_items / $this->per_page )
		) );
	}

	/**
	 * Displays the list of orders
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function display_content() {
		global $plugin_page;

		$this->prepare_items();
		?>
		<div class="wrap">
			<?php
			$this->views();
			?>
			<form id="<?php echo esc_attr( $plugin_page ); ?>_form" method="get">
				<?php $this->search_box( __( 'Search', BWS_BKNG_TEXT_DOMAIN ), 'bkng_search_order' ); ?>
				<input type="hidden" name="post_type" value="<?php echo esc_attr( $this->post_type ); ?>" />
				<input type="hidden" name="page" value="<?php echo esc_attr( $plugin_page ); ?>" />
				<input type="hidden" name="tab" value="history" />
				<?php $this->display(); ?>
			</form>
		</div>
	<?php }

	/**
	 * Fetch the value of orders filter item from the user request
	 * @since  0.1
	 * @access private
	 * @param  string     $fields      Fields' value from the database table that need to be selected
	 * @param  boolean    $add_limit   Whether to add order, limit and offset clauses to the request
	 * @return string
	 */
	private function get_query( $fields, $add_limit = false ) {

		/**
		 * Get the content of 'where' clause
		 */
		$where = " WHERE `user_id` = '" . get_current_user_id() . "'";
		if ( $this->s ) {
			$where .= " AND `id` LIKE '%" . $this->s . "%'";
		} elseif ( 'all' != $this->filter_status ) {
			$where .= " AND `status` = '" . $this->filter_status . "'";
		}

		$query = "SELECT " . $fields . " FROM `" . $this->db_table . "`" . $where;

		if ( $add_limit ) {
			$query .= " ORDER BY `" . $this->order_by . "` "  . $this->order . " LIMIT " . $this->per_page . " OFFSET " . $this->paged . ";";
		}

		return $query;
	}

	 /**
	 * Fetch the list of orders from the database
	 * @since  0.1
	 * @access private
	 * @param  bool	$add_limit
	 * @return void
	 */
	private function get_orders( $add_limit = true ) {
		global $wpdb, $bws_bkng;

		$data = $wpdb->get_results( $this->get_query( '`id`,`status`,`date_create`,`total`', $add_limit ) );

		if ( empty( $data ) ) {
			return false;
		}

		$items      = array();
		$statuses   = $bws_bkng->get_order_statuses();
		$unknown    = __( 'Unknown', BWS_BKNG_TEXT_DOMAIN );
		$currencies = $bws_bkng->data_loader->load( 'currencies' );

		foreach( $data as $order ) {
			$currencies_data = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT `meta_value` FROM `{$this->db_table}_meta` WHERE `meta_key` IN ( 'currency_code', 'currency_position' ) AND `order_id`=%d",
                    $order->id
                )
            );
			$status_label 	= array_key_exists( $order->status, $statuses ) ? $statuses[ $order->status ] : $unknown;
			$number       	= '<span class="bws_bkng_price">' . bws_bkng_number_format( $order->total ) . '</span>';
			$currency     	= empty( $currencies_data[0] ) ? '' : bws_bkng_get_currency( $currencies[ $currencies_data[0] ][1], $currencies_data[1] );
			$total        	= ! empty( $currencies_data[1] ) && 'left' == $currencies_data[1] ? "{$currency}{$number}" : "{$number}{$currency}";

			$products		= $this->get_products_by_order( $order->id );

			$items[] = array(
				'status_slug' 	=> $order->status,
				'status'      	=> $status_label,
				'id'          	=> key( $products ),
				'order_id'     	=> $order->id,
				'count'			=> count( $products ),
				'date'        	=> date_i18n( get_option( 'date_format' ), strtotime( $order->date_create ) ),
				'total'       	=> $total,
			);
		}

		return $items;
	}

	/**
	 * Fetch the list of products by order id
	 * @since  0.1
	 * @access private
	 * @param  int			The id of order
	 * @param  bool			whether return products only or all order data
	 * @return array|bool	$products or false if invalid order id
	 */
	private function get_products_by_order( $order_id, $whole_order = false ) {
		$order_class = BWS_BKNG_Order::get_instance();
		$order_data = $order_class->get( $order_id );

		return $whole_order ? $order_data : $order_data['products'];
	}
}