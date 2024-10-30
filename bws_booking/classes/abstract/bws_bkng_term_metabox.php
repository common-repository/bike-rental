<?php
/**
 * @uses     To add addditional data for terms
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Term_Metabox' ) )
	return;

abstract class BWS_BKNG_Term_Metabox {

	/**
	 * The slug of the term taxonomy.
	 * @since    0.1
	 * @var      string
	 * @access   protected
	 */
	protected $tax_slug;

	/**
	 * The child class name.
	 * Due to necessity of support down to PHP v5.2.4 we can't use late static binding
	 * or functions like get_called_class() to get child class from within the parent.
	 * @since    0.1
	 * @var      string
	 * @access   protected
	 */
	protected $child;

	/**
	 * Class constructor
	 * @since    0.1
	 * @access   public
	 * @param    string    $tax_slug   The slug of the term taxonomy.
	 * @param    string    $child      The child class name.
	 * @return   void
	 */
	public function __construct( $tax_slug, $child ) {
		$this->tax_slug = sanitize_title( $tax_slug );
		$this->child    = $child;
		$this->add_hooks();
	}

	/**
	 * Displays the term metaboxes
	 * @since    0.1
	 * @access   public
	 * @param    object|string    $tag             An instance of the WP_TERM class the taxonomy slug otherwise
	 * @param    string           $tax_slug        The term taxonomy slug
	 * @return   void
	 */
	public function add_meta_box( $tag, $tax_slug = null ) {
		global $bws_bkng;
		$is_edit_page = $tag instanceof WP_TERM;
		$tax_slug     = $is_edit_page ? $tax_slug : $tag;

		$items = $this->get_items( $tag, $tax_slug, $is_edit_page );

		if ( $is_edit_page ) { ?>
			<table class="form-table">
		<?php }

		foreach ( $items as $slug => $data )
			$this->add_row( $slug, $data, $is_edit_page );

		if ( $is_edit_page ) { ?>
			</table>
		<?php } else {
			/**
			 * uses to initialize the necessary class instance
			 * during the term data saving via AJAX-request
			 */
			$name  = 'bkng_child_class';
			$value = $this->child;
			echo $bws_bkng->get_hidden_input( compact( 'name', 'value' ) );
		}
	}

	/**
	 * Displays the term metabox content
	 * @since    0.1
	 * @access   public
	 * @param    string   $slug             The term slug
	 * @param    array    $data             Data to generate metabox
	 * @param    boolean  $is_edit_page     Whether the metabox will be displayed on the single term edit page or on the add-new-term page
	 * @return   void
	 */
	public function add_row( $slug, $data, $is_edit_page ) {
		if ( $is_edit_page ) { ?>
			<tr class="form-field">
				<th scope="row"><?php echo esc_html( $data['label'] ); ?></th>
				<td>
		<?php } else { ?>
			<div class="form-field term-<?php echo esc_attr( $slug ); ?>-wrap">
			<label><?php echo esc_html( $data['label'] ); ?></label>
		<?php }

		echo esc_html( $data['content'] );

		if ( $is_edit_page ) { ?>
				</td>
			</tr>
		<?php } else { ?>
			</div>
		<?php }
	}

	/**
	 * Adds necessary hooks in order to manage the term data
	 * @since    0.1
	 * @access   protected
	 * @param    void
	 * @return   void
	 */
	protected function add_hooks() {
		$tax_slug = $this->tax_slug;
		/* display additional form fields for the new taxonomy term */
		add_action( "{$tax_slug}_add_form_fields", array( $this, 'add_meta_box' ) );
		/* display additional form fields for the existed taxonomy term */
		add_action( "{$tax_slug}_edit_form", array( $this, 'add_meta_box' ), 10, 2 );
		/* save the new taxonomy term */
		add_action( "create_{$tax_slug}", array( $this, 'save_term_data' ), 10, 3 );
		/* save the existed terms data */
		add_action( "edit_{$tax_slug}", array( $this, 'save_term_data' ), 10, 2 );
	}

	/**
	 * Fetch the data for further displaying of the term metabox
	 * @since    0.1
	 * @access   protected
	 * @abstract
	 * @param    object|string $tag              An instance of the WP_TERM class the taxonomy slug otherwise
	 * @param    string        $tax_slug         The term taxonomy slug
	 * @param    boolean       $is_edit_page     Whether the metabox will be displayed on the single term edit page or on the add-new-term page
	 * @return   void
	 */
	abstract protected function get_items( $tag, $tax_slug, $is_edit_page );

	/**
	 * Saves the term
	 * @since    0.1
	 * @access   protected
	 * @abstract
	 * @param    int        $term_id      The term ID
	 * @param    int        $tt_id        The term taxonomy ID
	 * @param    string     $tax_slug     The term taxonomy slug
	 * @return   void
	 */
	abstract protected function save_term_data( $term_id, $tt_id, $tax_slug = '' );
}