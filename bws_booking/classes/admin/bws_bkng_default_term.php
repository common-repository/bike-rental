<?php
/**
 * @uses     To handle the defailt terms of the given taxonomy
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Default_Term' ) )
	return;

class BWS_BKNG_Default_Term {

	/**
	 * The slug of the given taxonomy
	 * @since  0.1
	 * @access private
	 * @var    string
	 */
	private $tax_slug;

	/**
	 * An instanc of the WP_taxonomy class of the given taxonomy
	 * @since  0.1
	 * @access private
	 * @var    object
	 */
	private $taxonomy;

	/**
	 * The default term taxonomy ID
	 * @since  0.1
	 * @access private
	 * @var    int
	 */
	private $default;


	/**
	 * Class constructor
	 * @since  0.1
	 * @access public
	 * @param   string   $taxonomy  The taxonomy slug
	 * @return  void
	 */
	public function __construct( $taxonomy ) {

		$this->tax_slug = sanitize_title( $taxonomy );
		$this->taxonomy = get_taxonomy( $this->tax_slug );
		$this->default  = absint( get_option( "default_{$this->tax_slug}" ) );

		/* Set new default term */
		if (
			isset( $_GET['action'] )   && 'set_as_default' == $_GET['action'] &&
			isset( $_GET['taxonomy'] ) && $this->tax_slug  == $_GET['taxonomy']
		) {
			$term_id = absint( $_REQUEST['term_id'] );
			check_admin_referer( "set-as-default-{$term_id}" );
			$this->process_actions( $term_id );
		}

		/* Adds default term */
		add_action( "created_{$this->tax_slug}", array( $this, 'set_default_term' ) );

		/* Adds "Set as default" link to the list of term taxonomies */
		add_filter( "{$this->tax_slug}_row_actions", array( $this, "add_default_link" ), 10, 2 );

		/* Adds some service messages */
		add_filter( "term_updated_messages", array( $this, "add_messages" ) );

		/* Adds default term taxonomy during the product saving */
		add_action( 'post_updated', array( $this, 'set_default_product_term' ), 10, 2 );

		/* Add the filter by the term of taxonomy on the products list page */
		add_action( 'restrict_manage_posts', array( $this, "add_filter" ) );

		/* Detelete the term hook */
		add_action( "delete_{$this->tax_slug}", array( $this, "change_product_term" ), 10, 4 );
	}

	/**
	 * Checks and set the default term taxonomy during the saving of term data
	 * @since  0.1
	 * @access public
	 * @param  int   $term_id   The ID of the given term
	 * @return void
	 */
	public function process_actions( $term_id ) {

		if ( ! current_user_can( $this->taxonomy->cap->edit_terms ) )
			return;

		$this->set_default_term( $term_id, true );

		/* Make redirect */
		$location = remove_query_arg(
			array( '_wp_http_referer', '_wpnonce', 'action', 'term_id' ),
			wp_unslash( $_SERVER['REQUEST_URI'] )
		);

		if ( ! empty( $_REQUEST['paged'] ) )
			$location = add_query_arg( 'paged', absint( $_REQUEST['paged'] ), $location );

		$location = add_query_arg(
			array(
				'message'   => 1,
				'post_type' => BWS_BKNG_POST
			),
			$location
		);

		wp_redirect( apply_filters( 'redirect_term_location', $location, $this->taxonomy ) );
		exit;
	}

	/**
	 * Sets the default term
	 * @since  0.1
	 * @access public
	 * @param  int      $term_id       The ID of the given term
	 * @param  bool     $set_anyway    Force option update if is "true"
	 */
	public function set_default_term( $term_id, $set_anyway = false ) {
		/* if the default term is missed it will save the currently edited as default */
		if ( empty( $this->default ) || $set_anyway ) {
			update_option( "default_{$this->tax_slug}", $term_id );
			$this->default = absint( $term_id );
		}
	}

	/**
	 * Adds "Set as default" link to the list of term taxonomies
	 * @since  0.1
	 * @access public
	 * @param  array      $actions       Row actions
	 * @param  object     $tag           An object of WP_Term class
	 * @return array      $actions
	 */
	public function add_default_link( $actions, $tag ) {

		if ( $this->default == $tag->term_id || ! current_user_can( $this->taxonomy->cap->edit_terms ) )
			return $actions;

		$actions['set_as_default'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			wp_nonce_url( "edit-tags.php?action=set_as_default&amp;taxonomy={$this->tax_slug}&amp;term_id={$tag->term_id}", "set-as-default-{$tag->term_id}" ),
			esc_attr( __( 'Set as default', BWS_BKNG_TEXT_DOMAIN ) ),
			__( 'Set as default', BWS_BKNG_TEXT_DOMAIN )
		);

		return $actions;
	}

	/**
	 * Adds service messages
	 * @since  0.1
	 * @see    wp-admin/includes/edit-tag-messages.php
	 * @access public
	 * @param  array      $messages      The list of registered service messages
	 * @return array      $messages
	 */
	public function add_messages( $messages ) {
		if ( empty( $messages[ $this->tax_slug ] ) )
			$messages[ $this->tax_slug ] = array();

		$messages[ $this->tax_slug ][0] = '';
		$messages[ $this->tax_slug ][1] = __( 'Default term is changed', BWS_BKNG_TEXT_DOMAIN );

		return $messages;
	}

	/**
	 * Bind the product to the default term of taxonomy during saving it
	 * @since     0.1
	 * @access    public
	 * @param     int      $post_ID
	 * @param     object   $post_after
	 * @return    void
	 */
	public function set_default_product_term( $post_ID, $post_after ) {

		if ( BWS_BKNG_POST != $post_after->post_type )
			return;

		if ( ! has_term( '', $this->tax_slug, $post_after ) )
			wp_set_object_terms( $post_ID, $this->default, $this->tax_slug );
	}

	/**
	 * All product that are binded to the deleted term will be binded to the default
	 * @since     0.1
	 * @access    public
	 * @param     int      $term           Term ID.
	 * @param     int      $tt_id          Term taxonomy ID.
	 * @param     mixed    $deleted_term   Copy of the already-deleted term, in the form specified by the parent function.
	 *                                     WP_Error otherwise.
	 * @param     array    $object_ids     List of term object IDs.
	 * @return    void
	 */
	public static function change_product_term( $term, $tt_id, $deleted_term, $object_ids = '' ) {
		global $wp_version, $wpdb;

		if ( is_wp_error( $deleted_term ) || empty( $deleted_term->taxonomy ) || empty( $object_ids ) || ! is_array( $object_ids ) )
			return;

		$default_category = get_option( "default_{$deleted_term->taxonomy}" );

		foreach ( $object_ids as $id )
			wp_set_object_terms( $id, absint( $default_category ), $deleted_term->taxonomy );
	}

	/**
	 * Adds the dropdown list in order to filter products
	 * @since     0.1
	 * @access    public
	 * @param     string   $post_type   The post type slug ( @since 4.4.0 )
	 * @return    void
	 */
	public function add_filter( $post_type = '' ) {
		global $typenow;

		$post_type = empty( $post_type ) ? $typenow : $post_type;

		if ( BWS_BKNG_POST != $post_type )
			return;

		$terms = get_terms( $this->tax_slug );

		if ( ! is_array( $terms ) || empty( $terms ) )
			return;

		$current  = isset( $_GET[ $this->tax_slug ] ) && ! empty( $_GET[ $this->tax_slug ] ) ? sanitize_text_field( stripslashes( $_GET[ $this->tax_slug ] ) ) : '';
		$tax_name = $this->taxonomy->labels->name; ?>

		<select name="<?php echo esc_attr( $this->tax_slug ); ?>" id="<?php echo esc_attr( $this->tax_slug ); ?>" class="">
			<option value='0'><?php echo __( 'All', BWS_BKNG_TEXT_DOMAIN ) . '&nbsp;' . esc_html( $tax_name ); ?></option>
			<?php foreach ( $terms as $term ) {
				$selected = $term->slug == $current ? ' selected="selected"' : ''; ?>
				<option value="<?php echo esc_attr( $term->slug ); ?>"<?php echo esc_attr( $selected ); ?>><?php echo esc_html( $term->name ) ?></option>
			<?php } ?>
		</select>
	<?php }
}