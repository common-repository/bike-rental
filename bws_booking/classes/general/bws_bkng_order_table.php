<?php
/**
 * Displays the order data with the Table layout
 * @uses     in the Booking cart and during the mail sending
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Order_Table' ) )
	return;

class BWS_BKNG_Order_Table {

	/**
	 * Contains the list of table columns names
	 * @since  0.1
	 * @access private
	 * @var    array
	 */
	private $columns;

	/**
	 * Contains the data to be displayed in a table
	 * @since  0.1
	 * @access private
	 * @var    array
	 */
	private $data;

	/**
	 * Contains the currency are used on the site
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $currency;

	/**
	 * Contains the currency positiion on the site
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $currency_position;

	/**
	 * Whether the order will be sent via email
	 * @since  0.1
	 * @access private
	 * @var    boolean
	 */
	private $is_for_mail;

	/**
	 * Class instance
	 * @since    0.1
	 * @access   public
	 * @param    array   $data         Order data
	 * @param    array   $columns      The list of the table columns
	 * @return   void
	 */
	public function __construct( $data, $columns ) {

		$this->data     = $data;
		$this->columns  = $columns;
		$this->currency = bws_bkng_get_currency();
		$this->currency_position = bws_bkng_get_currency_position();
	}

	/**
	 * Fetch the list of column slugs
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   array
	 */
	private function get_columns() {
		return array_keys( $this->columns );
	}

	/**
	 * Fetch the table content
	 * @since    0.1
	 * @access   public
	 * @param    boolean     $for_mail  Whether the order will be sent via email
	 * @return   string
	 */
	public function get( $for_mail = false ) {
		$this->is_for_mail = !! $for_mail;
		ob_start();
		$this->display();
		return ob_get_clean();
	}

	/**
	 * Displays the table content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function display() {
		$columns = $this->get_columns();
		$colspan = count( $columns ) - 1;
		$styles  = array(
			'table_style', 'head_style', 'body_style', 'foot_style',
			'head_cell_style', 'head_title_cell_style',
			'body_cell_style', 'body_title_cell_style',
			'foot_cell_style', 'foot_title_cell_style'
		);
		foreach( $styles as $style ) {
			if ( ! $this->is_for_mail ) {
				$$style = '';
			} else {
				switch( $style ) {
					case 'table_style':
						$$style = 'width: 100%; margin: 10px 0;border-spacing: 0;border-collapse: collapse;';
						break;
					case 'head_style':
						$$style = 'background: #d9d9d9; font-weight: bold;';
						break;
					case 'head_cell_style':
					case 'head_title_cell_style':
					case 'body_cell_style':
						$$style = 'padding: 5px;border: 1px solid #ccc;';
						break;
					case 'body_title_cell_style':
						$$style = 'padding: 5px;border: 1px solid #ccc;';
						break;
					case 'foot_title_cell_style':
						$$style = 'text-align: right; padding: 5px; font-weight: bold;';
						break;
					default:
						$$style = '';
						break;
				}
				$$style = apply_filters( "bws_bkng_order_table_style", $$style, $style );
				if ( ! empty( $$style ) )
					$$style = " style=\"{$$style}\"";
			}
		}

		do_action( 'bws_bkng_before_order_table', $this->data ); ?>

		<table class="bws_bkng_order_table"<?php echo esc_attr( $table_style ); ?>>
			<thead<?php echo esc_attr( $head_style ); ?>>
				<tr>
					<?php foreach( $this->columns as $slug => $label ) { ?>
						<td class="bws_bkng_order_table_column bws_bkng_order_table_column_<?php echo esc_attr( $slug ); ?>"<?php echo 'title' == $slug ? esc_attr( $head_title_cell_style ) : esc_attr( $head_cell_style ); ?>>
							<?php echo esc_html( $label ); ?>
						</td>
					<?php } ?>
				</tr>
			</thead>
			<tbody<?php echo esc_attr( $body_style ); ?>>
				<?php foreach( $this->data['products'] as $item ) { ?>
					<tr>
						<?php foreach( $columns as $column ) { ?>
							<td class="bws_bkng_order_table_column bws_bkng_order_table_column_<?php echo esc_attr( $column ); ?>"<?php echo 'title' == $column ? esc_attr( $body_title_cell_style ) : esc_attr( $body_cell_style ); ?>>
								<?php if ( isset( $item[ $column ] ) )
									echo esc_html( $item[ $column ] );
								else
									do_action( 'bws_bkng_order_table_column', $column, $item ); ?>
							</td>
						<?php } ?>
					<tr>
				<?php } ?>
			</tbody>
			<tfoot<?php echo esc_attr( $foot_style ) ;?>>
				<?php if ( isset( $this->data['subtotal'] ) ) { ?>
					<tr class="bws_bkng_order_subtotal">
						<td colspan="<?php echo esc_attr( $colspan ); ?>" class="bws_bkng_order_subtotal_title"<?php echo esc_attr( $foot_title_cell_style ); ?>><?php _e( 'Subtotal', BWS_BKNG_TEXT_DOMAIN ); ?>:</td>
						<td style="padding-left: 7px;" class="bws_bkng_order_subtotal_value"<?php echo esc_attr( $foot_cell_style ); ?>><?php echo esc_html( $this->data['subtotal'] ); ?></td>
					</tr>
				<?php } ?>
				<?php if ( isset( $this->data['total'] ) ) { ?>
					<tr class="bws_bkng_order_total">
						<td colspan="<?php echo esc_attr( $colspan ); ?>" class="bws_bkng_order_total_title"<?php echo esc_attr( $foot_title_cell_style ); ?>><?php _e( 'Total', BWS_BKNG_TEXT_DOMAIN ); ?>:</td>
						<td style="padding-left: 7px;" class="bws_bkng_order_total_value"<?php echo esc_attr( $foot_cell_style ); ?>><?php echo esc_html( $this->data['total'] ); ?></td>
					</tr>
				</tfoot>
			<?php } ?>
		</table><!-- .bws_bkng_order_table -->

		<?php do_action( 'bws_bkng_after_order_table', $this->data );
	}
}
