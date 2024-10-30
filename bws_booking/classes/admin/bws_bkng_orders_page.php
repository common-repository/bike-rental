<?php
/**
 * Contains the functionality that handles the displaying of the list of orders
 * @since	0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Orders_Page' ) )
	return;

if ( !class_exists('WP_List_Table') )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class BWS_BKNG_Orders_Page extends WP_List_Table {
	private static $instance = NULL;

	/**
	 * Contains the status slug by which the orders list is filtered
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $filter_status;

	/**
	 * Contains the user name by which the orders list is filtered
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $filter_user;

	/**
	 * Contains the month and year by which the orders list is filtered
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $filter_month;

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
	 * Contains the prefix of database tables where  products data are kept
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $db_table;

	private $post_type;


	/**
	 * Contains the name of the action nonce
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $nonce = 'bkng_order_list_nonce';

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
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function __construct() {
		global $status, $page;

		/* Set parent defaults */
		parent::__construct( array(
			'singular' => __( 'order', BWS_BKNG_TEXT_DOMAIN ),
			'plural'   => __( 'orders', BWS_BKNG_TEXT_DOMAIN ),
			'ajax'     => false
		));

		if ( isset( $_REQUEST['post_type'] ) ) {
			$this->post_type = sanitize_text_field( stripslashes( $_REQUEST['post_type'] ) );
        }

		$this->db_table = BWS_BKNG_DB_PREFIX . $this->post_type . '_orders';

		$result = $this->process_action();

		if ( ! empty( $result ) )
			$this->clear_query( $result );
	}

	/**
	 * Show message if item list is empty
	 * @since	0.1
	 * @access   public
	 * @param  void
	 * @return void
	 */
	public function no_items() { ?>
		<p><?php _e( 'No orders found', BWS_BKNG_TEXT_DOMAIN ); ?></p>
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
			'cb'       => '<input type="checkbox" />',
			'order'    => __( 'Order', BWS_BKNG_TEXT_DOMAIN ),
			'customer' => __( 'Customer', BWS_BKNG_TEXT_DOMAIN ),
			'status'   => __( 'Status', BWS_BKNG_TEXT_DOMAIN ),
			'message'  => __( 'Customer Note', BWS_BKNG_TEXT_DOMAIN ),
			'total'    => __( 'Total', BWS_BKNG_TEXT_DOMAIN ),
			'date'     		 => __( 'Date', BWS_BKNG_TEXT_DOMAIN ),
		);
		return $columns;
	}

	 /**
	 * Get the list of sortable columns.
	 * @since	0.1
	 * @access   public
	 * @param  void
	 * @return array   An associative array containing all the columns
	 *				 that should be sortable: 'slugs'=>array('data_values',bool)
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'order' => array( 'id', false ),
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
	 * @return string				Text or HTML to be placed inside the column <td>
	 */
	public function column_default( $item, $column_name ){
		switch( $column_name ){
			case 'cb':
			case 'status':
			case 'order':
			case 'customer':
			case 'total':
			case 'date':
			case 'message':
			// case 'payment_id':
			// case 'payment_status':
			// case 'payment_date':
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
	 * @return  string			   with the column content
	 */
	public function column_cb( $item ) {
		return "<input type=\"checkbox\" name=\"bkng_order_id[]\" value=\"{$item['id']}\"/>";
	}

	/**
	 * Displays the contnet of the "Order" column
	 * @since	0.1
	 * @access   public
	 * @see	WP_List_Table::single_row_columns()
	 * @param  array   $item   A singular item (one full row's worth of data)
	 * @return string		  Text to be placed inside the column <td>
	 */
	public function column_order( $item ) {
		global $plugin_page;

		$post       = $this->post_type;
		$link       = '<a href="%1$s">%2$s</a>';
		$action_url = admin_url( "edit.php?post_type={$post}&amp;page={$plugin_page}&amp;bkng_order_id={$item['id']}" );
		$actions = array(
			'edit'   => sprintf( $link, "{$action_url}&amp;action=edit", __( 'Edit', BWS_BKNG_TEXT_DOMAIN ) ),
			'delete' => sprintf( $link, wp_nonce_url( $action_url, $this->nonce ) . "&amp;action=delete", __( 'Delete', BWS_BKNG_TEXT_DOMAIN ) )
		);

		$order_title = '<strong>' . sprintf( $link, "{$action_url}&amp;action=edit", "#{$item['id']}" ) . '</strong>';

		return  $order_title . $this->row_actions( $actions );
	}

	public function column_total( $item ) {
		return ( $this->is_price_on_request( $item['id'] ) ? '<span class="bkng_info_icon dashicons dashicons-info" title="' . __( 'Price on Request', BWS_BKNG_TEXT_DOMAIN ) .'"></span>' : '' ) . $item['total'];
	}

	/**
	 * Add necessary css classes depending on order status
	 * @since	0.1
	 * @access   public
	 * @param     array     $item        The current link data.
	 * @return    void
	 */
	public function single_row( $item ) {
		echo "<tr class=\"bkng_order_status_" . esc_attr( $item['status_slug'] ) . "\">";
			$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Add action links before and after list of messages
	 * @since	0.1
	 * @access   public
	 * @param  void
	 * @return string   list of action links
	 */
	public function get_views() {
		global $wpdb, $bws_bkng, $plugin_page;
		$links = array();

		$statuses = $bws_bkng->get_order_statuses();

		$query = "SELECT COUNT(`id`) AS `all`, %s FROM `{$this->db_table}`;";
		$query_parts = $filters = array();

		foreach( array_keys( $statuses ) as $status ) {
			$status = esc_sql( $status );
			$query_parts[] = "( SELECT COUNT(`id`) FROM `{$this->db_table}` WHERE `status`='{$status}' ) AS `{$status}`";
		}

		$counts = $wpdb->get_results( sprintf( $query, implode( ',', $query_parts ) ) );

		if ( empty( $counts[0] ) || $wpdb->last_error )
			return '';

		$statuses['all'] = __( 'All', BWS_BKNG_TEXT_DOMAIN );

		foreach ( $counts[0] as $key => $value ) {
			$class = $key == $this->filter_status ? ' class="current"' : '';
			$url   = '?post_type=' . $this->post_type . '&page=' . $plugin_page . '&bkng_filter_status=' . $key;
			$filters[ $key ] = "<a href=\"{$url}\"{$class}>{$statuses[$key]}&nbsp;<span class=\"count\">({$value})</span></a>";
		}
		return $filters;
	}

	/**
	 * Get an associative array ( option_name => option_title ) with the list
	 * of bulk actions available on this table.
	 *
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete' => __( 'Delete', BWS_BKNG_TEXT_DOMAIN )
		);
		return $actions;
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @since  0.1
	 * @access public
	 * @param  string   $which   'top' or 'bottom' - the block location relative to the orders list
	 * @return void
	 */
	public function extra_tablenav( $which ) {
		global $wpdb, $bws_bkng;
		$filters = '';

		$monts_list = $wpdb->get_col(
			"SELECT DATE_FORMAT(`date_create`, '%Y-%m-01') AS `months`
			FROM `{$this->db_table}` GROUP By `months`;"
		);

		if ( ! empty( $monts_list ) && 1 < count( $monts_list ) ) {
			$name     = "bkng_filter_month_{$which}";
			$selected = esc_attr( $this->filter_month );
			$options  = array( '-1' => __( 'All dates', BWS_BKNG_TEXT_DOMAIN ) );

			foreach( $monts_list as $date )
				$options[ $date ] = date_i18n( 'F Y', strtotime( $date ) );

			$filters .= $bws_bkng->get_select( compact( 'name', 'options', 'selected' ) );
		}

		$name  = "bkng_filter_user_{$which}";
		$value = esc_attr( $this->filter_user );
		$attr  = 'placeholder="' . __( 'All Customers', BWS_BKNG_TEXT_DOMAIN ) . '"';
		$filters .= $bws_bkng->get_text_input( compact( 'name', 'value', 'attr' ) );

		$name  = "bkng_filter_orders_{$which}";
		$value = __( 'Filter', BWS_BKNG_TEXT_DOMAIN );
		$class = 'button action';
		$filters .= $bws_bkng->get_button_input( compact( 'name', 'value', 'class' ) );

		echo "<div class=\"alignleft actions bulkactions bkng_filters\">{$filters}</div>";
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

		if ( 'top' === $which )
			wp_nonce_field( $this->nonce, '_wpnonce', 0 ); ?>

		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<?php if ( $this->has_items() ) { ?>
				<div class="alignleft actions bulkactions">
					<?php $this->bulk_actions( $which ); ?>
				</div>
			<?php } ?>
			<?php $this->extra_tablenav( $which );
			$this->pagination( $which ); ?>

			<br class="clear" />
		</div>
	<?php }

	/**
	 * Handle the order list bulk requests.
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return boolen|array    An array with the current action and
	 *                         the number of rows from the database that were affected
	 *                         during the action handling, false otherwise
	 */
	public function process_action() {
		global $wpdb;

		$action = $this->current_action();

		if ( ! $action || 'delete' != $action || empty( $_REQUEST['bkng_order_id'] ) )
			return false;

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], $this->nonce ) )
			die( __( 'Oops, something went wrong', BWS_BKNG_TEXT_DOMAIN ) );

		/**
		 * There is for now only one action for orders form the list - delete it(them)
		 */
		$orders = array_map( 'absint', (array)$_REQUEST['bkng_order_id'] );

		if ( empty( $orders ) )
			return false;

		$orders = implode( ',', $orders );
		$count  = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM `{$this->db_table}` WHERE `id` IN(%s);",
                $orders
            )

        );
		$result = $action;

		return compact( 'result', 'count' );

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
		$month = $this->get_filter( 'month' );

		$this->filter_month  = '-1' != $month && $bws_bkng->is_valid_date_format( $month ) ? $month : '';
		$this->filter_status = isset( $_REQUEST['bkng_filter_status'] ) && in_array( $_REQUEST['bkng_filter_status'], array_keys( $bws_bkng->get_order_statuses() ) ) ? sanitize_text_field( stripslashes( $_REQUEST['bkng_filter_status'] ) ) : 'all';
		$this->filter_user   = $this->get_filter( 'user' );

		$this->order_by = isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $sortable_columns ) ) ? $sortable_columns[ $_REQUEST['orderby'] ][0] : 'id';
		$this->order    = isset( $_REQUEST['order'] ) && in_array( strtoupper( $_REQUEST['order'] ), array( 'ASC', 'DESC' ) ) ? $_REQUEST['order'] : 'desc';
		$this->paged    = isset( $_REQUEST['paged'] ) && is_numeric( $_REQUEST['paged'] ) ? $_REQUEST['paged'] - 1 : 0;
		$this->s        = isset( $_REQUEST['s'] ) ? sanitize_text_field( stripslashes( $_REQUEST['s'] ) ) : '';
		$this->per_page = $this->get_items_per_page( 'bkng_per_page', 20 );
		$this->_column_headers = array( $this->get_columns(), array(), $sortable_columns );
		$this->items           = $this->get_orders();

		$total_items = absint( $wpdb->get_var( $this->get_query( 'COUNT(`id`)' ) ) );

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
	public function display_table() {
		global $title, $plugin_page;
		$this->prepare_items(); ?>
		<div class="wrap">
			<h1><?php echo esc_html( $title ); ?></h1>
			<?php $this->show_notices();
			$this->views(); ?>
			<form id="bkng_orders_form" method="get">
				<?php $this->search_box( __( 'Search', BWS_BKNG_TEXT_DOMAIN ), 'bkng_search_order' ); ?>
				<input type="hidden" name="page" value="<?php echo esc_attr( $plugin_page ); ?>" />
				<input type="hidden" name="post_type" value="<?php echo esc_attr( $this->post_type ); ?>" />
				<?php $this->display() ?>
			</form>
		</div>
	<?php }

	/**
	 * Fetch the value of orders filter item from the user request
	 * @since  0.1
	 * @access public
	 * @param  string    $by
	 * @return string
	 */
	private function get_filter( $by ) {
		$from_which = array( 'top', 'bottom' );

		foreach( $from_which as $which ) {

			if ( empty( $_REQUEST["bkng_filter_orders_{$which}"] ) )
				continue;

			return empty( $_REQUEST["bkng_filter_{$by}_{$which}"] ) ? '' : sanitize_text_field( stripslashes( $_REQUEST["bkng_filter_{$by}_{$which}"] ) );
		}

		return '';
	}

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
		if ( $this->s ) {
			$where = " WHERE `user_message` LIKE '%{$this->s}%' ";
		} else {
			$where_parts = array();

			if ( 'all' != $this->filter_status )
				$where_parts[] = "`status`='{$this->filter_status}'";

			if ( $this->filter_user ) {
				$where_parts[] =
					"( `user_firstname` LIKE '%{$this->filter_user }%'
						OR `user_lastname` LIKE '%{$this->filter_user }%'
						OR `user_email` LIKE '%{$this->filter_user }%' )";
			}

			if ( $this->filter_month ) {
				$timestamp = strtotime( $this->filter_month );
				$month = date( 'm', $timestamp );
				$year  = date( 'Y', $timestamp );

				if ( $month == 2 )
					$days_in_month = $year % 4 ? 28 : 29;
				else
					$days_in_month = ( $month - 1 ) % 14 ? 30 : 31;

				$days_in_month .= 10 > $days_in_month ? '0' : '';
				$where_parts[] = "( `date_create` BETWEEN '{$this->filter_month} 00:00:00' AND '{$year}-{$month}-{$days_in_month} 00:00:00' )";
			}

			$where = empty( $where_parts ) ? '' : ' WHERE ' . implode( ' AND ', $where_parts );
		}

		$query = "SELECT {$fields} FROM `{$this->db_table}` {$where}";

		if ( $add_limit )
			$query .= "ORDER BY `{$this->order_by}` {$this->order} LIMIT {$this->per_page} OFFSET " . $this->paged * $this->per_page . ";";

		return $query;
	}

	/**
	 * Remove exceed query parameters and makes a redirect to the current page in order to avoid
	 * re-handling the request during page re-loading.
	 * @since  0.1
	 * @access private
	 * @param  array     $args     An array of data that need to be added to the url
	 * @return void
	 */
	private function clear_query( $args ) {
		$sendback = remove_query_arg( array( '_wpnonce', 'bkng_order_id', 'action' ), wp_get_referer() );
		$sendback = add_query_arg( $args, $sendback );
		wp_redirect( add_query_arg( $args, $sendback ) );
		exit();
	}

	/**
	 * Displays the necessary message after the handling of the request
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return void
	 */
	private function show_notices() {

		if ( ! isset( $_GET['result'] ) || ! isset( $_GET['count'] ) )
			return;

		$formated = number_format_i18n( $_GET['count'] );
		$count    = absint( $_GET['count'] );

		if ( ! $count )
			return;

		$message = sprintf(
			_n(
				"One order has been removed",
				"%s orders have been removed",
				$formated,
				BWS_BKNG_TEXT_DOMAIN
			),
			$count
		);

		if ( ! empty( $message ) ) { ?>
			<div class="updated fade inline notice notice-success is-dismissible">
				<p><?php echo esc_html( $message ); ?></p>
				<button type="button" class="notice-dismiss"></button>
			</div>
		<?php }
	}

	private function is_price_on_request( $order_id ) {
		global $wpdb;
        $post_type = sanitize_text_field( stripslashes( $_GET['post_type'] ) );

		return !! $wpdb->get_var(
		        $wpdb->prepare(
                    "SELECT `meta_value`
                    FROM `{$wpdb->postmeta}`
                    WHERE `meta_key`='bkng_price_on_request' AND
                        `meta_value`='1' AND
                        `post_id` IN (
                            SELECT `product_id`
                            FROM `" . BWS_BKNG_DB_PREFIX . $post_type . "_ordered_products`
                            WHERE `order_id`=%d
                        )
                    LIMIT 1;",
                    absint( $order_id )
                )

		);
	}

	 /**
	 * Fetch the list of orders from the database
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return void
	 */
	private function get_orders() {
		global $wpdb, $bws_bkng;

		$data = $wpdb->get_results( $this->get_query( '`id`,`status`,`date_create`,`user_id`,`user_firstname`,`user_lastname`,`user_message`,`total`', true ) );

		if ( empty( $data ) )
			return false;

		$items      = array();
		$statuses   = $bws_bkng->get_order_statuses();
		$unknown    = __( 'Unknown', BWS_BKNG_TEXT_DOMAIN );
		$currencies = $bws_bkng->data_loader->load( 'currencies' );


		foreach( $data as $order ) {
			$currencie_data = $wpdb->get_col( "SELECT `meta_value` FROM `{$this->db_table}_meta` WHERE `meta_key` IN ( 'currency_code', 'currency_position' ) AND `order_id`={$order->id}" );
			$status_label = array_key_exists( $order->status, $statuses ) ? $statuses[ $order->status ] : $unknown;
			$user_link    = empty( $order->user_id ) ? '' : get_edit_user_link( $order->user_id );
			$user_name    = "{$order->user_firstname}&nbsp;{$order->user_lastname}";
			$customer     = empty( $user_link ) ? $user_name  : "<a href=\"{$user_link}\">{$user_name}</a>";
			$number       = '<span class="bws_bkng_price">' . bws_bkng_number_format( $order->total ) . '</span>';
			$style        	= '';
			$currency     = empty( $currencie_data[0] ) ? '' : bws_bkng_get_currency( $currencies[ $currencie_data[0] ][1], $currencie_data[1] );
			$total        = ! empty( $currencie_data[1] ) && 'left' == $currencie_data[1] ? "{$currency}{$number}" : "{$number}{$currency}";

			$items[] = array(
				'status_slug' => $order->status,
				'status'      => strtolower( $status_label ),
				'id'          => $order->id,
				'customer'    => $customer,
				'date'        => date_i18n( get_option( 'date_format' ), strtotime( $order->date_create ) ),
				'total'       => $total,
				'message'   	 => esc_html( $order->user_message ),
			);
		}
		return $items;
	}
}
