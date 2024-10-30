<?php
/**
 * Manage the products additional data
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Custom_Type_Metabox' ) )
	return;

class BWS_BKNG_Custom_Type_Metabox {

	private static $instance = NULL;

	private $post_type;
	private $post_type_display;
	private $post_relations_table;
	private $not_fount_text;
	private $add_new_text;
	private $add_new_link;

	/**
	 * Class constructor
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void|bool
	 */
	public function __construct( $args ) {
		global $bws_bkng, $pagenow;

		if( ! isset( $args['post_type'] ) ) {
			return false;
		}
        $this->post_type				= $args['post_type'];
		$this->post_type_display	    = $args['post_type_display'];
		$this->post_relations_table     = $args['post_relations_table'];
		$this->not_fount_text			= $args['not_fount_text'];
		$this->add_new_text				= $args['add_new_text'];
		$this->add_new_link				= $args['add_new_link'];

		add_meta_box(
			$bws_bkng->plugin_prefix . '_' . $this->post_type . '_metabox',
			$args['metabox_title'],
			array( $this, 'display_metabox' ),
			$this->post_type,
			'side',
			'default'
		);
	
		add_action( 'save_post_' . $this->post_type, array( $this, 'save_post' ), 10, 2 );
		add_action( 'delete_post_' . $this->post_type, array( $this, 'delete_post' ), 10 );
	}

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
	 * Saves the product additional attributes
	 * @see    BWS_BKNG_Admin::__construct()
	 * @since  0.1
	 * @access public
	 * @param  int        $post_id     The ID of currently managed product
	 * @param  object     $post        The ID of currently managed product - an instance of the class WP_Post
	 * @return int        $post_id
	 */
	public function save_post( $post_id, $post ) {
		global $bws_bkng, $wpdb, $bws_allow_multiple_relations;
		/**
		 * Don't do anything if:
		 * - there is a revision
		 * - the user doesn't have edit permissions
		 */
		if ( wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}
		if( ! isset( $_POST[ $bws_bkng->plugin_prefix . '_add_' . $this->post_type . '_nonce' ] ) ||
            ! wp_verify_nonce(
                    $_POST[ $bws_bkng->plugin_prefix . '_add_' . $this->post_type . '_nonce' ],
                    $bws_bkng->plugin_prefix . '_add_' . $this->post_type . '_' . $post_id
            )
        ) {
			return $post_id;
		}

        $new_related_posts = $_POST[ $bws_bkng->plugin_prefix . '_' . $this->post_type . '_id' ]
            ? array_map( 'intval', $_POST[ $bws_bkng->plugin_prefix . '_' . $this->post_type . '_id' ] )
            : false;

        if ( ! $new_related_posts ) {
            $query = $wpdb->prepare(
                "DELETE FROM `$this->post_relations_table` WHERE {$this->post_type}_id = %d",
                $post_id
            );
            $wpdb->query( $query );
            return $post_id;
        }

        $query = $wpdb->prepare(
            "SELECT `{$this->post_type_display}_id` FROM `$this->post_relations_table` WHERE {$this->post_type}_id = %d",
            $post_id
        );
        $related_posts = $wpdb->get_col( $query );

        $related_posts_to_add = array_diff( $new_related_posts, $related_posts );
        $related_posts_to_delete = array_diff( $related_posts, $new_related_posts );

        if ( $related_posts_to_add && $bws_allow_multiple_relations[$this->post_type] ) {
            foreach( $related_posts_to_add as $related_post_id ) {
                $wpdb->insert(
                    $this->post_relations_table,
                    [
                        "{$this->post_type}_id" => $post_id,
                        "{$this->post_type_display}_id" => $related_post_id
                    ]
                );
            }
        } else {
            $wpdb->insert(
                $this->post_relations_table,
                [
                    "{$this->post_type}_id" => $post_id,
                    "{$this->post_type_display}_id" => $related_posts_to_add[0]
                ]
            );
        }

        if ( $related_posts_to_delete ) {
            foreach( $related_posts_to_delete as $related_post_id ) {
                $wpdb->delete(
                    $this->post_relations_table,
                    [
                        "{$this->post_type}_id" => $post_id,
                        "{$this->post_type_display}_id" => $related_post_id
                    ]
                );
            }
        }

        return $post_id;
	}

	/**
	 * Removes all variations during the product deleting
	 * @see
	 * @since  0.1
	 * @access public
	 * @param  int       $post_id    The  ID of the primary post
	 * @return void
	 */
	public function delete_post( $post_id ) {
		wp_set_object_terms( $post_id, null, $this->tax_slug );
	}

	/**
	 * Displays the metabox "Preferences"
	 * @see    self::add_meta_box()
	 * @since  0.1
	 * @access public
	 * @param  WP_Post  $post    The primary product object - an instance of the class WP_Post
	 * @return void
	 */
	public function display_metabox( $post ) {
		global $wpdb, $bws_bkng;

		$custom_results = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT `ID`, `post_title` FROM `' . $wpdb->posts . '` WHERE `post_type` = %s  AND `post_status` = "publish"',
                $this->post_type_display
            ),
            ARRAY_A
        );
        $table = BWS_BKNG_DB_PREFIX . "{$this->post_type}_{$this->post_type_display}_relations";
        $sql = $wpdb->prepare("SELECT {$this->post_type_display}_id FROM `$table` WHERE {$this->post_type}_id = %d", $post->ID );
        $current_custom = $wpdb->get_col( $sql ); ?>
        <div class="<?php echo esc_attr( $this->post_type_display ); ?>div">
			<ul id="<?php echo esc_attr( $bws_bkng->plugin_prefix ); ?>_<?php echo esc_attr( $this->post_type_display ); ?>-all" class="<?php echo esc_attr( $this->post_type_display ); ?>checklist form-no-clear">
				<?php /* Take category for current slider */
				if ( ! empty( $custom_results ) ) {
					/* Display all categories in metabox */
					foreach ( $custom_results as $custom ) { ?>
						<li>
							<input type="checkbox" name="<?php echo esc_attr( $bws_bkng->plugin_prefix . '_' . $this->post_type . '_id' ); ?>[]" value="<?php echo esc_attr( $custom['ID'] ); ?>"<?php if ( ! empty( $current_custom ) && in_array( $custom['ID'], $current_custom ) ) echo 'checked="checked"'; ?> />
							<?php echo esc_html( $custom['post_title'] ); ?>
						</li>
					<?php }					
				} else { ?>
					<i><?php echo esc_html( $this->not_fount_text ); ?></i>
				<?php } ?>
			</ul>
			<?php wp_nonce_field( $bws_bkng->plugin_prefix . '_add_' . $this->post_type . '_' . $post->ID, $bws_bkng->plugin_prefix . '_add_' . $this->post_type . '_nonce' ); ?>
		</div>
		<div id="category-adder" class="wp-hidden-children">
			<a id="category-add-toggle" href="<?php echo esc_url( $this->add_new_link ); ?>" class="taxonomy-add-new">+ <?php echo esc_html( $this->add_new_text ); ?></a>
		</div>
	<?php }
}
