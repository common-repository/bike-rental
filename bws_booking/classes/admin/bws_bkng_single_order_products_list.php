<?php
/**
 * Contains the functionality to display the list of
 * ordered products on the edit order page.
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

if ( class_exists( 'BWS_BKNG_Single_Order_Products_List' ) )
	return;

class BWS_BKNG_Single_Order_Products_List extends WP_List_Table {

	/**
	 * Contains the currency siggn
	 * that was actual at the moment of placing an order
	 * @since 0.1
	 * @access private
	 * @var string
	 */
	private $currency;

	/**
	 * Contains the currency position
	 * that was actual at the moment of placing an order
	 * @since 0.1
	 * @access private
	 * @var string
	 */
	private $currency_position;

	/**
	 * Contains the order subtotal
	 * @since 0.1
	 * @access private
	 * @var float
	 */
	private $subtotal;

	/**
	 * Contains the order total
	 * @since 0.1
	 * @access private
	 * @var float
	 */
	private $total;

	/**
	 * Contains the order status slug
	 * @since 0.1
	 * @access private
	 * @var string
	 */
	private $order_status;

	/**
	 * Whether to show the tooltip near the order total
	 * @since 0.1
	 * @access private
	 * @var string
	 */
	private $show_total_tooltip;

	/**
	 * Class constructor
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	function __construct( $order ) {
		global $bws_bkng;

		/* Set parent defaults */
		parent::__construct( array(
			'singular' => __( 'product', BWS_BKNG_TEXT_DOMAIN ),
			'plural'   => __( 'products', BWS_BKNG_TEXT_DOMAIN ),
			'ajax'     => false
		));
		$currencies               = $bws_bkng->data_loader->load( 'currencies' );
		$this->_column_headers    = array( $this->get_columns(), array(), array() );
		$this->items              = $this->parse_list( $order['products'] );
		$this->order_status       = $order['status'];
		$this->total              = $order['total'];
		$this->subtotal           = $order['subtotal'];
		$this->currency           = $currencies[ $order['currency_code'] ][1];
		$this->currency_position  = $order['currency_position'];
		$this->show_total_tooltip = false;

		add_action( 'admin_footer', array( $this, 'add_scripts' ) );
	}

	/**
	 * Display the ordered products' data in order to handle the order changes via javascript.
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function add_scripts() {
		global $bws_bkng, $wp_scripts;
		$bws_bkng->add_datepicker_scripts();
		echo "<script>var " . BWS_BKNG_POST . " = " . wp_json_encode( $this->sanitize_script_data( $this->items ) ) . ';</script>';
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
	 * @since  0.1
	 * @access public
	 * @see    WP_List_Table::single_row_columns()
	 * @param  void
	 * @return array	An associative array containing column information: 'slugs'=>'Visible Titles'
	 */
	public function get_columns() {
		$columns = array(
			'title'         => __( 'Product', BWS_BKNG_TEXT_DOMAIN ),
			'price'         => __( 'Price', BWS_BKNG_TEXT_DOMAIN ),
			'quantity'      => __( 'Quantity', BWS_BKNG_TEXT_DOMAIN ),
			'total'         => __( 'Summary', BWS_BKNG_TEXT_DOMAIN ),
			'info'          => ""
		);
		return $columns;
	}

	/**
	 * Form the content of column "Products"
	 * @since  0.1
	 * @access public
	 * @param  array     $item   The current item
	 * @return string            The THML-structeure to display
	 */
	public function column_title( $item ) {
		$title = empty( $item['edit_post_link'] ) ? "%s{$item['title']}" : "%s<a href=\"{$item['edit_post_link']}\">{$item['title']}</a>";
		if ( empty( $item['linked_to'] ) )
			$title = "<strong>{$title}</strong>";
		$sku   = empty( $item['sku'] ) ? '' : "<i title=\"SKU\">({$item['sku']})</i>&nbsp;";
		$title = sprintf( $title, $sku );
		return
			"<div class=\"bkng_product_thumbnail\">
				<img src=\"{$item['thumb']}\" alt=\"{$item['title']}\" title=\"{$item['title']}\" />
			</div>
			<div class=\"bkng_product_title\">
				{$title}
			</div>" ;
	}

	/**
	 * Form the content of column "Quantity"
	 * @since  0.1
	 * @access public
	 * @param  array     $item   The current item
	 * @return string            The THML-structeure to display
	 */
	public function column_quantity( $item ) {
		global $bws_bkng;

		$name  = "bkng_quantity[{$item['id']}]";
		$value = $item['quantity'];
		$min   = 1;
		$max   = absint( get_post_meta( $item['id'], 'bkng_in_stock', true ) );
		$attr  = 'on_hold' == $this->order_status && $max >= $value ? '' : 'readonly="readonly"';
		$class = "bkng_quantity_input";

		return $bws_bkng->get_number_input( compact( 'name', 'value', 'min', 'max', 'class', 'attr' ) );
	}

	/**
	 * Forms the content of column "Price"
	 * @since  0.1
	 * @access public
	 * @param  array     $item   The current item
	 * @return string            The THML-structeure to display
	 */
	public function column_price( $item ) {
		$rent_interval = bws_bkng_get_rent_interval( $item['id'] );
		return $this->format_value( $item['price'] ) . ( empty( $rent_interval ) ? '' : "&nbsp;{$rent_interval}" );
	}

	/**
	 * Forms the content of column "Subtotal"
	 * @since  0.1
	 * @access public
	 * @param  array     $item   The current item
	 * @return string            The THML-structeure to display
	 */
	public function column_subtotal( $item ) {
		return $this->format_value( $item['subtotal'] );
	}

	/**
	 * Forms the content of column "Subtotal"
	 * @since  0.1
	 * @access public
	 * @param  array     $item   The current item
	 * @return string            The THML-structeure to display
	 */
	public function column_total( $item ) {
		return $this->format_value( $item['total'] );
	}

	/**
	 * Forms the content of column "Info"
	 * @since  0.1
	 * @access public
	 * @param  array     $item   The current item
	 * @return string            The THML-structeure to display
	 */
	public function column_info( $item ) {
		$show_icon = get_post_meta( $item['id'], 'bkng_price_on_request', true );

		if ( $show_icon ) {
			$this->show_total_tooltip = true;
			return "<span class=\"bkng_info_icon dashicons dashicons-info\" title=\"" . __( 'Price on request', BWS_BKNG_TEXT_DOMAIN ) . "\"></span>";
		}

		return '';
	}

	/**
	 * Generates content for a single row of the table
	 * @since  0.1
	 * @access public
	 * @param  array $item The current item
	 * @return void
	 */
	public function single_row( $item ) {
		echo "<tr data-id=\"" . esc_attr( $item['id'] ) . "\">";
		$this->single_row_columns( $item );
		echo "</tr>";
	}

	/**
	 * Displays the table
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function display() {
		global $bws_bkng;
		$singular = $this->_args['singular']; ?>
		<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
			<thead>
				<tr><?php $this->print_column_headers(); ?></tr>
			</thead>

			<tbody id="the-list"<?php if ( $singular ) echo " data-wp-lists='list:" . esc_attr( $singular ) . "'";  ?>>
				<?php $this->display_rows_or_placeholder(); ?>
			</tbody>
		</table>
		<div class="bkng_order_total_wrap<?php if ( $this->show_total_tooltip ) echo ' bkng_padding_right'; ?>">
			<div id="bkng_order_subtotal" class="bkng_order_total">
				<strong class="bkng_order_total_label"><?php _e( 'Subtotal', BWS_BKNG_TEXT_DOMAIN ); ?>:</strong>
				<span class="bkng_order_total_value"><?php echo $this->format_value( $this->subtotal ); ?></span>
			</div>
			<div id="bkng_order_total" class="bkng_order_total">
				<strong class="bkng_order_total_label"><?php _e( 'Total', BWS_BKNG_TEXT_DOMAIN ); ?>:</strong>
				<span class="bkng_order_total_value"><?php echo $this->format_value( $this->total ); ?></span>
				<?php if ( $this->show_total_tooltip )
					echo $bws_bkng->get_tooltip( __( 'The price of some product was not shown to the user.', BWS_BKNG_TEXT_DOMAIN ), '', '', 'info' ); ?>
			</div><!-- .bkng_order_total_wrap -->
		</div>
	<?php }

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
			case 'thumb':
			case 'title':
			case 'quantity':
			case 'price':
			case 'total':
			case 'subtotal':
				return $item[ $column_name ];
			case 'info':
				return '';
			default:
				return print_r( $item, true );
		}
	}

	/**
	 * Prepare the products data bfeore displaying it.
	 * @see    self::add_scripts()
	 * @since  0.1
	 * @access private
	 * @param  mixed  $item   the list of products data or the single product data
	 * @return void
	 */
	private function sanitize_script_data( $item ) {

		if ( is_array( $item ) ) {
			$data = array();

			foreach( $item as $key => $value )
				$data[ $key ] = $this->sanitize_script_data( $value );

			return $data;
		}

		return html_entity_decode( $item, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Prepare the products price ( subtotal, total, etc. ).
	 * according to the order data
	 * @since  0.1
	 * @access private
	 * @param  number  $number   The raw value
	 * @return string            The Html-structure to display
	 */
	private function format_value( $number ) {
		$number   = '<span class="bws_bkng_price">' . bws_bkng_number_format( $number ) . '</span>';
		$currency = bws_bkng_get_currency( $this->currency, $this->currency_position );

		return 'left' == $this->currency_position ? "{$currency}{$number}" : "{$number}{$currency}";
	}

	/**
	 * Adds additional data to the products data list
	 * @since  0.1
	 * @access private
	 * @param  array     $products  Products data
	 * @return array
	 */
	private function parse_list( $products ) {
		global $bws_bkng;

		$default_url = $bws_bkng->get_default_image_src();

		foreach ( $products as $id => $product ) {

			$post      = get_post( $id );
			$thumb_url =
					$bws_bkng->allow_variations &&
					! empty( $post->post_type ) &&
					BWS_BKNG_VARIATION == $post->post_type &&
					! empty( $post->post_parent )
				?
					get_the_post_thumbnail_url( $post->post_parent )
				:
					get_the_post_thumbnail_url( $id );

			if ( empty( $thumb_url ) )
				$thumb_url = $default_url;

			$products[ $id ] = array_merge(
				$products[ $id ],
				array(
					'id'    => $id,
					'thumb' => $thumb_url
				)
			);
		}

		return $products;
	}
}
