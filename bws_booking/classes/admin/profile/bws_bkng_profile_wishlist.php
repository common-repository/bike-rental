<?php
/**
 * Handle the content of "Settings" tab of user profile
 * @since  Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Profile_Wishlist' ) )
	return;

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class BWS_BKNG_Profile_Wishlist extends WP_List_Table {
	
	/**
	 * Contains class instance
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $user_class;

	/**
	 * Contains the status slug by which the wishlist is filtered
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $filter_status;

	/**
	 * Contains the field by which the wishlist is ordered
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $order_by;

	/**
	 * Contains the order direction (asc, desc) by which the wishlist is ordered
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $order;

	/**
	 * Contains the number of displayed products per page
	 * @since  0.1
	 * @access private
	 * @var    int
	 */
	private $per_page;

	/**
	 * Contains the number currently displayed products page
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
	private $nonce = 'bkng_product_list_nonce';

	/**
	 * Class constructor
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function __construct() {

		$this->user_class 	= BWS_BKNG_User::get_instance();
		$this->post_type = sanitize_text_field( stripslashes( $_REQUEST['post_type'] ) );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		$result = $this->process_action();

		if ( ! empty( $result ) ) {
			$this->clear_query( $result );
		}

		/* Set parent defaults */
		parent::__construct( array(
			'singular' => __( 'product', BWS_BKNG_TEXT_DOMAIN ),
			'plural'   => __( 'products', BWS_BKNG_TEXT_DOMAIN ),
			'ajax'     => true
		) );
	}

	/**
	 * Show message if item list is empty
	 * @since	0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function no_items() { ?>
		<p><?php _e( 'No items in your wishlist', BWS_BKNG_TEXT_DOMAIN ); ?></p>
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
			'cb'       			=> '<input type="checkbox" />',
			'featured-image'	=> __( 'Featured Image', BWS_BKNG_TEXT_DOMAIN ),
			'product'  			=> __( 'Product', BWS_BKNG_TEXT_DOMAIN ),
			'price'    			=> __( 'Price', BWS_BKNG_TEXT_DOMAIN ),
			'attributes'    	=> __( 'Attributes', BWS_BKNG_TEXT_DOMAIN ),
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
			'price' => array( 'price', false ),
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
			case 'product':
			case 'price':
				return $item[ $column_name ];
			case 'featured-image':
				return $this->column_featured_image( $item );
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
		return '<input type="checkbox" name="bkng_product_id[]" value="' . $item['id'] . '" />';
	}

	/**
	 * Outputs the content of columns
	 * @since	0.1
	 * @access   public
	 * @see	WP_List_Table::single_row_columns()
	 * @param  array   $item   A singular item (one full row's worth of data)
	 * @return string		  Image to be placed inside the column <td>
	 */
	public function column_featured_image( $item ) {
		global $bws_bkng;

		$thumb = get_the_post_thumbnail( $item['id'], array( 100, 100 ) );
		return empty( $thumb ) ? '<img width="100" src="' . $bws_bkng->get_default_image_src() . '" />' : $thumb;
	}

	public function column_product( $item ) {
		$link       = '<a href="%1$s" %3$s>%2$s</a>';
		$action_url = wp_nonce_url( add_query_arg( 'bkng_product_id', $item['id'] ), $this->nonce );
		$actions = array(
			'rent'   => sprintf(
				$link,
				get_post_permalink( $item['id'] ),
				__( 'Rent', BWS_BKNG_TEXT_DOMAIN ),
				'data-action="order" data-product-id="' . $item['id'] . '"' ),
			'delete'  => sprintf( $link, $action_url . "&amp;action=delete", __( 'Remove', BWS_BKNG_TEXT_DOMAIN ), '' )
		);

		$product_title = '<strong>' . sprintf( $link, get_post_permalink( $item['id'] ), $item['product'], '' ) . '</strong>';

		return $product_title . $this->row_actions( $actions );
	}

	public function column_price( $item ) {
		return ( ! bws_bkng_show_product_price( $item['id'] ) ? '<span class="bkng_info_icon dashicons dashicons-info" title="' . __( 'Price on Request', BWS_BKNG_TEXT_DOMAIN ) .'"></span>' : '' ) . $item['price'];
	}

	public function column_attributes( $item ) {
		$post_data		= new BWS_BKNG_Post_Data( $item['id'] );
		$attributes		= $post_data->get_custom_attribute_list();

		if ( $attributes ) {
			$output = '<div class="bkng-grid-table">';
			$limit = 3;

			foreach ( $attributes as $attr ) {
				$output .= '<span>' . $attr['label'] . ':&nbsp;</span>';
				$output .= implode( ', ', $attr['value'] );
				if ( ! --$limit ) {
					$output .= '<br>...';
					break;
				}
			}

			$output .= '</div>';
		} else {
			$output = __( 'There is no attributes for this product', BWS_BKNG_TEXT_DOMAIN );
		}

		return $output;
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
			'delete' => __( 'Remove', BWS_BKNG_TEXT_DOMAIN )
		);
		return $actions;
	}

	/**
	 * Generate the table navigation above or below the table.
	 * This function was added in order to overwrite nonce fields from
	 * the parent class.
	 * @since  0.1
	 * @access public
	 * @param  string $which
	 * @return void
	 */
	public function display_tablenav( $which ) {

		if ( 'top' === $which ) {
			wp_nonce_field( $this->nonce, '_wpnonce', 0 );
		} ?>

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
	 * Handle the wishlist bulk requests.
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return bool|array    An array with the current action and
	 *                       the number of rows from the database that were affected
	 *                       during the action handling, false otherwise
	 */
	public function process_action() {
		global $wpdb;

		$action = $this->current_action();

		if ( ! $action || 'delete' != $action || empty( $_REQUEST['bkng_product_id'] ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], $this->nonce ) ) {
			die( __( 'Oops, something went wrong', BWS_BKNG_TEXT_DOMAIN ) );
		}

		/**
		 * There is for now only one action for wishlist form the list - remove it(them)
		 */
		$bkng_product_id = absint( $_REQUEST['bkng_product_id'] );
		$this->user_class->remove_from_user_wishlist( $bkng_product_id );

		$count  = count( $_REQUEST['bkng_product_id'] );
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
		global $bws_bkng;

		$sortable_columns = $this->get_sortable_columns();

		$this->filter_status = isset( $_REQUEST['bkng_filter_status'] ) && in_array( $_REQUEST['bkng_filter_status'], array_keys( $bws_bkng->get_order_statuses() ) ) ? $_REQUEST['bkng_filter_status'] : 'all';

		$this->order_by 		= isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $sortable_columns ) ) ? $sortable_columns[ sanitize_text_field( stripslashes( $_REQUEST['orderby'] ) ) ][0] : 'id';
		$this->order    		= isset( $_REQUEST['order'] ) && in_array( strtoupper( $_REQUEST['order'] ), array( 'ASC', 'DESC' ) ) ? sanitize_text_field( stripslashes( $_REQUEST['order'] ) ) : 'desc';
		$this->paged    		= isset( $_REQUEST['paged'] ) && is_numeric( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 0;
        $this->s        		= isset( $_REQUEST['s'] ) ? sanitize_text_field( stripslashes( $_REQUEST['s'] ) ) : '';
		$this->per_page 		= $this->get_items_per_page( 'bkng_per_page', 20 );
		$this->_column_headers 	= array( $this->get_columns(), array(), $sortable_columns );
		$this->items           	= $this->get_wishlist();
		$total_items 			= count( $this->items );
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $this->per_page,
			'total_pages' => ceil( $total_items / $this->per_page )
		) );
	}

	/**
	 * Displays the wishlist
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function display_content() {
		global $plugin_page, $bws_bkng;

		$this->prepare_items();
		?>
		<div class="wrap">
			<?php
			$this->show_notices();
			$this->views();
			if ( $bws_bkng->is_pro ) { ?>
                <form id="<?php echo esc_attr( $plugin_page ); ?>_form" method="get">
                    <?php $this->search_box( __( 'Search', BWS_BKNG_TEXT_DOMAIN ), 'bkng_search_order' ); ?>
                    <input type="hidden" name="page" value="<?php echo esc_attr( $plugin_page ); ?>" />
                    <input type="hidden" name="post_type" value="<?php echo esc_attr( $this->post_type ); ?>" />
                    <input type="hidden" name="tab" value="wishlist" />
                    <?php $this->display(); ?>
                </form>
            <?php } else {
			    $this->wishlist_block();
            } ?>
		</div>
		<?php
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
		$sendback = remove_query_arg( array( '_wpnonce', 'bkng_product_id', 'action' ), wp_get_referer() );
		$sendback = add_query_arg( $args, $sendback );
		print( '<script>window.location.href="' . $sendback . '"</script>' );
	}

	/**
	 * Displays the necessary message after the handling of the request
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return void
	 */
	private function show_notices() {
		if ( ! isset( $_GET['result'], $_GET['count'] ) ) {
			return;
		}

		$formated = number_format_i18n( $_GET['count'] );
		$count    = absint( $_GET['count'] );

		if ( ! $count ) {
			return;
		}

		$message = sprintf(
			_n(
				"One item has been removed from your wishlist",
				"%s items have been removed from your wishlist",
				$formated,
				BWS_BKNG_TEXT_DOMAIN
			),
			$count
		);

		if ( ! empty( $message ) ) {
			?>
			<div class="updated fade inline notice notice-success is-dismissible">
				<p><?php echo esc_html( $message ); ?></p>
				<button type="button" class="notice-dismiss"></button>
			</div>
			<?php
		}
	}

	 /**
	 * Fetch the wishlist
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return array
	 */
	private function get_wishlist() {
		global $wpdb, $bws_bkng;
		
		$ids = $this->user_class->get_user_wishlist();

		if ( empty( $ids ) ) {
			return false;
		}

		$items      = array();
		$products = get_posts( array( 'post_type' => $this->post_type, 'include' => $ids, 's' => $this->s ) );

		foreach ( $products as $product ) {
			$items[] = array(
				'id'          	=> $product->ID,
				'product'     	=> $product->post_title,
				'price'       	=> bws_bkng_price_format( bws_bkng_get_product_price( $product->ID ) ),
			);
		}
		$order_get = isset( $_GET['order'] ) ? sanitize_text_field( stripslashes( $_GET['order'] ) ) : 'asc';
		if ( 'asc' == $order_get) {
			array_multisort( $items,SORT_ASC, array_column( $items, 'price') );
		} else if ( 'desc' == $order_get ) {
			array_multisort( $items,SORT_DESC, array_column( $items, 'price') );
		}
		return $items;
	}

	private function wishlist_block() {
	    global $bws_bkng, $wp_version;
        $bws_settings_tabs = new Bws_Settings_Tabs( array( 'plugins_info' => $bws_bkng->get_plugin_info() ) ); ?>
        <div class="bws_pro_version_bloc">
            <div class="bws_pro_version_table_bloc">
                <div class="bws_table_bg"></div>
                <div class="bws_pro_version">
                    <div id="bkng_user_profile_form">
                        <p class="search-box">
                            <label class="screen-reader-text" for="bkng_search_order-search-input">Search:</label>
                            <input type="search" id="bkng_search_order-search-input" name="s" value="">
                            <input type="submit" id="search-submit" class="button" value="Search">
                        </p>
                    <input type="hidden" name="page" value="bkng_user_profile">
                    <input type="hidden" name="post_type" value="bws_bike">
                    <input type="hidden" name="tab" value="wishlist">
                    <input type="hidden" id="_wpnonce" name="_wpnonce" value="547d269bca">
                    <div class="tablenav top">

                        <div class="alignleft actions bulkactions">
                            <label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label><select name="action" id="bulk-action-selector-top">
                                <option value="-1">Bulk actions</option>
                                <option value="delete">Remove</option>
                            </select>
                            <input type="submit" id="doaction" class="button action" value="Apply">
                        </div>
                        <div class="tablenav-pages one-page">
                            <span class="displaying-num">1 item</span>
                            <span class="pagination-links">
                                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
                                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
                                <span class="paging-input"><label for="current-page-selector" class="screen-reader-text">Current Page</label><input class="current-page" id="current-page-selector" type="text" name="paged" value="1" size="1" aria-describedby="table-paging"><span class="tablenav-paging-text"> of <span class="total-pages">1</span></span></span>
                                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
                                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>
                            </span>
                        </div>
                        <br class="clear">
                    </div>
                    <table class="wp-list-table widefat fixed striped table-view-list products">
                        <thead>
                            <tr>
                                <td id="cb" class="manage-column column-cb check-column">
                                    <label class="screen-reader-text" for="cb-select-all-1">
                                        Select All
                                    </label>
                                    <input id="cb-select-all-1" type="checkbox">
                                </td>
                                <th scope="col" id="featured-image" class="manage-column column-featured-image column-primary">
                                    Featured Image
                                </th>
                                <th scope="col" id="product" class="manage-column column-product">
                                    Product
                                </th>
                                <th scope="col" id="price" class="manage-column column-price sortable desc">
                                    <a style="cursor: pointer">
                                        <span>Price</span>
                                        <span class="sorting-indicator"></span>
                                    </a>
                                </th>
                                <th scope="col" id="attributes" class="manage-column column-attributes">
                                    Attributes
                                </th>
                            </tr>
                        </thead>

                        <tbody id="the-list" data-wp-lists="list:product">
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="bkng_product_id[]" value="3059">
                                </th>
                                <td class="featured-image column-featured-image has-row-actions column-primary" data-colname="Featured Image">
                                    <svg width="100" height="100">
                                        <rect width="100" height="100" style="fill: #aaa" />
                                    </svg>
                                    <button type="button" class="toggle-row">
                                        <span class="screen-reader-text">Show more details</span>
                                    </button>
                                </td>
                                <td class="product column-product" data-colname="Product">
                                    <strong><a style="cursor: pointer">Lorem Ipsum</a></strong>
                                    <div class="row-actions">
                                        <span class="rent">
                                            <a data-action="order" data-product-id="3059" style="cursor: pointer">
                                                Rent
                                            </a>
                                            |
                                        </span>
                                        <span class="delete">
                                            <a style="cursor: pointer">
                                                Remove
                                            </a>
                                        </span>
                                    </div>
                                    <button type="button" class="toggle-row">
                                        <span class="screen-reader-text">Show more details</span>
                                    </button>
                                </td>
                                <td class="price column-price" data-colname="Price">
                                    <div class="bws_bkng_product_price_column">
                                        <span class="bws_bkng_currency">$</span>
                                        <span class="bws_bkng_product_price">35.00</span>
                                    </div>
                                </td>
                                <td class="attributes column-attributes" data-colname="Attributes">
                                    <div class="bkng-grid-table">
                                        <span>Bike Brand:&nbsp;</span>
                                        Lorem Ipsum
                                        <span>Features:&nbsp;</span>
                                        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus eu cursus massa, sed fringilla dolor.
                                        <span>Intended For:&nbsp;</span>
                                        Male/Female
                                        <br>
                                        ...
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <label class="screen-reader-text" for="cb-select-all-2">Select All</label>
                                <input id="cb-select-all-2" type="checkbox">
                            </td>
                            <th scope="col" class="manage-column column-featured-image column-primary">Featured Image</th>
                            <th scope="col" class="manage-column column-product">Product</th>
                            <th scope="col" class="manage-column column-price sortable desc">
                                <a style="cursor: pointer">
                                    <span>Price</span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th scope="col" class="manage-column column-attributes">Attributes</th>
                        </tr>
                        </tfoot>
                    </table>
                    <div class="tablenav bottom">
                        <div class="alignleft actions bulkactions">
                            <label for="bulk-action-selector-bottom" class="screen-reader-text">Select bulk action</label>
                            <select name="action2" id="bulk-action-selector-bottom">
                                <option value="-1">Bulk actions</option>
                                <option value="delete">Remove</option>
                            </select>
                            <input type="submit" id="doaction2" class="button action" value="Apply">
                        </div>
                        <div class="tablenav-pages one-page">
                            <span class="displaying-num">1 item</span>
                            <span class="pagination-links">
                                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
                                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
                                <span class="screen-reader-text">Current Page</span>
                                <span id="table-paging" class="paging-input">
                                    <span class="tablenav-paging-text">
                                        1 of
                                        <span class="total-pages">1</span>
                                    </span>
                                </span>
                                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>
                                <span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>
                            </span>
                        </div>
                        <br class="clear">
                    </div>
                </div>
                </div>
            </div>
            <?php $bws_settings_tabs->bws_pro_block_links( 'https://bestwebsoft.com/products/wordpress/plugins/' . $bws_bkng->wp_slug . '/?k=' . $bws_bkng->link_key . '&pn=' . $bws_bkng->link_pn . '&v='. $bws_settings_tabs->plugins_info['Version'] . '&wp_v=' . $wp_version ); ?>
        </div>
    <?php }
}