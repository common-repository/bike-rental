<?php
/**
 * Manages the binding between products
 * @uses     On the edit products page
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Products_Tree' ) )
	return;

class BWS_BKNG_Products_Tree {

	/**
	 * THe list of products categories
	 * @since  0.1
	 * @access private
	 * @var    array
	 */
	private $cats_list;

	/**
	 *THe list of products categories which are binded to current product
	 * @since  0.1
	 * @access private
	 * @var array
	 */
	private $linked_cats_list;

	/**
	 * Contains the list of products IDs that need to be excluded from adding to database
	 * @uses   If the user earlier bind some category to the product but then excluded some certain products from it
	 * @since  0.1
	 * @access private
	 * @var    array   Format:
	 * array(
	 * 	{type} => array(
	 * 		{cat_id} => array( {prod_id}, {prod_id}, ..., {prod_id} ),
	 * 		...
	 * 		{cat_id} => array( {prod_id}, {prod_id}, ..., {prod_id} )
	 * 	),
	 * 	{type} => array( ... ),
	 * 	...
	 * 	{type} => array( ... )
	 * )
	 * , where {type} is the type of binding ( 'extras' - is only available by default, but via 'bws_bkng_trees' filter they can be more )
	 *
	 */
	private $temp_storage;

	/**
	 * Contains the list of IDs products and categories for further action
	 * @since  0.1
	 * @access private
	 * @var array
	 */
	private $save_actions = array (
		'cat_add'        => array(),
		'cat_remove'     => array(),
		'product_add'    => array(),
		'product_remove' => array(),
	);

	/**
	 * Enque tree JS-scripts and CSS-styles
	 * @since  0.1
	 * @access public
	 * @static
	 * @param  void
	 * @return void
	 */
	public static function enque_scripts() {
		$fancy_url = BWS_BKNG_URL . "assets/fancytree/";
		wp_enqueue_style( 'bkng_fancytree', "{$fancy_url}ui.fancytree.min.css" );

		wp_register_script(
			'bkng_fancytree_lib',
			"{$fancy_url}jquery.fancytree.js",
			array(
				'jquery',
				'jquery-ui-core',
				'jquery-effects-core',
				'jquery-effects-blind',
				'jquery-ui-widget'
			),
			false,
			true
		);

		wp_enqueue_script(
			'bkng_fancytree',
			BWS_BKNG_URL . "js/fancytree_handle.js",
			array( 'bkng_fancytree_lib' ),
			false,
			true
		);

		$args = array(
			'nonce'  => wp_create_nonce( 'bkng_ajax_nonce' ),
			'loader' => array(
				'title' => sprintf( '<span class="bkng_tree_loading">%s</span>', __( 'Loading', BWS_BKNG_TEXT_DOMAIN ) ),
				'key'   => 'bkng_more_anchor'
			),
			'error' => __( 'Oops, something went wrong. Check, please', BWS_BKNG_TEXT_DOMAIN )
		);
		wp_localize_script( 'bkng_fancytree', 'bkng_fancytree_vars', $args );
	}

	/**
	 * Fetch the list of categories
	 * @since  0.1
	 * @access public
	 * @static
	 * @param  void
	 * @return array    $categories
	 */
	public static function get_categories() {
		global $bws_bkng, $wpdb;
		$post_id    = absint( $_POST['bkng_post_id'] );
		$type       = sanitize_title( $_POST['bkng_tree_type'] );
		$categories = array();
		$terms      = $bws_bkng->get_terms( BWS_BKNG_CATEGORIES, array( 'hide_empty' => false ) );
		$table      = BWS_BKNG_DB_PREFIX . 'linked_products';
		$post_types = "'" . implode( "','", $bws_bkng->get_post_types() ) . "'";

		if ( empty( $post_id ) || is_wp_error( $terms ) || empty( $terms ) )
			return array();

		/* Get the linked cats list */
		$linked_cats = (array)$wpdb->get_col(
		    $wpdb->prepare(
                "SELECT `category_id`
                FROM `{$table}`
                WHERE `post_id` = %d
                    AND `product_id` IS NULL
                    AND `type` = %s;",
                $post_id,
                $type
            )
		);

		foreach( $terms as $term ) {

			$is_selected = in_array( $term->term_id, $linked_cats );

			$total_posts = absint( $wpdb->get_var(
			    $wpdb->prepare(
                    "SELECT COUNT( `p`.`ID` )
                    FROM `{$wpdb->posts}` AS `p`
                    LEFT JOIN `{$wpdb->prefix}term_relationships` AS `t`
                        ON `t`.`term_taxonomy_id`=%d
                    WHERE `p`.`ID`=`t`.`object_id`
                        AND `p`.`post_type` IN (%s)
                        AND `p`.`post_status`='publish';",
                    $term->term_id,
                    $post_types
                )
			) );

			/*
			 * If the category isn't binded to the product comepletely
			 * try to get amount of all products from the category
			 */
			if ( ! $is_selected ) {
				$linked_posts = absint( $wpdb->get_var(
				    $wpdb->prepare(
                        "SELECT COUNT( `post_id` )
                        FROM `{$table}`
                        WHERE `post_id`=%d
                            AND `category_id`=%d
                            AND `product_id` IS NOT NULL
                            AND `type`=%s;",
                        $post_id,
                        $term->term_id,
                        $type
                    )
				) );
				/*
				 * Check again if all products from category were linked
				 * in case if products weren't linked before via JS Fancytree functionality
				 * (eg. during demo data loading )
				 */
				$is_selected = ! empty( $total_posts ) && ! empty( $linked_posts ) && $total_posts === $linked_posts;
			}

			$cat_data = array(
				'title'          => $term->name,
				'key'            => $term->slug,
				'cat'            => $term->slug,
				'id'             => $term->term_id,
				'folder'         => true,
				'lazy'           => true,
				'partsel'        => $is_selected ? false : ! empty( $linked_posts ),
				'selected_count' => $is_selected ? $total_posts : absint( $linked_posts ),
				'total_posts'    => $total_posts
			);

			if ( ! $cat_data['partsel'] )
				$cat_data['selected'] = $is_selected;

			$categories[] = $cat_data;
		}

		return $categories;
	}

	/**
	 * Fetch list of products in the category
	 * @since  0.1
	 * @access public
	 * @static
	 * @param  void
	 * @return array    $products
	 */
	public static function get_products() {
		global $bws_bkng, $wpdb;
		$post_id     = absint( $_POST['bkng_post_id'] );
		$type        = sanitize_title( $_POST['bkng_tree_type'] );
		$category    = sanitize_title( $_POST['bkng_category'] );
		$next_page   = absint( $_POST['bkng_next_page'] );
		$total_posts = absint( $_POST['bkng_total_posts'] );
		$per_page    = absint( $_POST['bkng_per_page'] );
		$position    = strval( $_POST['bkng_position'] );
		$table       = BWS_BKNG_DB_PREFIX . 'linked_products';

		/* whether the category heckbox was marked */
		$is_cat_selected = 'partsel' == $_POST['bkng_selected'] ? 'partsel' : filter_var( $_POST['bkng_selected'], FILTER_VALIDATE_BOOLEAN );

		/* get the scroll direction in order to get the number of paginated page */
		$is_downscroll = 'bottom' == $position;

		$posts = get_posts( array(
			'posts_per_page' => $per_page,
			'post_type'      => $bws_bkng->get_post_types(),
			'post__not_in'   => array( $post_id ),
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
			'offset'         => ( 1 < $next_page ? absint( $next_page - 1 ) * $per_page : 0 ),
			'tax_query'      => array(
				array(
					'taxonomy' => BWS_BKNG_CATEGORIES,
					'field'    => 'slug',
					'terms'    => $category,
				)
			)
		) );

		if ( empty( $posts ) )
			return array();
		/*
		 * If the whole category wasn't bind to the product
		 * try to search binded product from this category that are boinede to the currently edited
		 */
		if ( true !== $is_cat_selected ) {
			$post_ids = implode( ',', $bws_bkng->array_map( 'array_column', $posts, 'ID' ) );
			$term     = get_term_by( 'slug', $category, BWS_BKNG_CATEGORIES );

			$linked_posts =
					empty( $post_ids ) || empty( $term->term_id )
				?
					array()
				:
					(array)$wpdb->get_col(
					    $wpdb->prepare(
                            "SELECT `product_id`
                            FROM `{$table}`
                            WHERE `post_id`=%d
                                AND `category_id`=%d
                                AND `product_id` IN (%s)
                                AND `type`=%s;",
                            $post_id,
                            $term->term_id,
                            $post_ids,
                            $type
                        )
					);
		}

		$products = array();
		foreach( $posts as $post ) {
			$title = $bws_bkng->allow_variations && BWS_BKNG_VARIATION == $post->post_type ? get_the_title( $post->post_parent ) : $post->post_title;
			$sku   = get_post_meta( $post->ID, 'bkng_sku', true );
			if ( empty( $sku ) )
				$sku = "#$post->ID";

			$product = array(
				'title'    => "$title&nbsp;<i>({$sku})<i>",
				'key'      => $post->ID,
				'id'       => $post->ID,
				'cat'      => $category,
				'selected' => 'partsel' === $is_cat_selected ? in_array( $post->ID, $linked_posts ) : $is_cat_selected,
				'current_page' => $next_page
			);
			$products[] = $product;
		}

		/*
		 * Add additional CSS-class to the first item
		 * in order to auto scroll to it after the loading of the next list part
		 */
		$products[0]['extraClasses'] = "bkng_scroll_to_top_{$category}_{$next_page}";

		/*
		 * Add anchors to detect on order to load next list part if the page was crolled to them
		 */
		if ( self::is_paged( $total_posts, $per_page ) ) {
			/* Previous list part anchor */
			if ( ! $is_downscroll && ! self::is_first_page( $next_page ) )
				array_unshift( $products, self::get_anchor( $category, $next_page - 1, 'top' ) );
			/* Next page list part */
			elseif ( $is_downscroll && ! self::is_last_page( $total_posts, $next_page, $per_page ) )
				array_push( $products, self::get_anchor( $category, $next_page + 1, 'bottom' ) );
		}

		return $products;
	}

	/**
	 * Save the tree data to database
	 * @since  0.1
	 * @access public
	 * @static
	 * @param  int      $post_id    The currently edited product ID
	 * @param  string   $type       The type of binding
	 * @param  array    $data       Data to be saved
	 * @return array
	 */
	public static function save_tree( $post_id = 0, $type = '', $data = '' ) {

		$post_id    = absint( $post_id ) ?: absint( $_POST['bkng_post_id'] );
		$type       = empty( $type ) ? sanitize_title( $_POST['bkng_tree_type'] ) : $type;
		$data       = is_array( $data ) ? $data : array_map( 'sanitize_text_field', $_POST['bkng_tree_data'] );

		$instance = new self();

		/**
		 * Prepare data before saving them
		 */
		foreach ( $data as $key => $node ) {

			$is_selected           = ! empty( $node['selected'] ) && filter_var( $node['selected'], FILTER_VALIDATE_BOOLEAN );
			/* if it is a single product form category */
			if ( is_numeric( $key ) && empty( $node['folder'] ) ) {
				$cat                   = $node['cat'];
				$is_cat_linked         = $instance->is_cat_linked( $cat, $post_id, $type );
				$is_cat_will_be_linked = ! empty( $data[ $cat ] ) && ! empty( $data[ $cat ]['folder'] ) && filter_var( $data[ $cat ]['selected'], FILTER_VALIDATE_BOOLEAN );
				/* If the category will be linked */
				if ( $is_cat_will_be_linked ) {

					/* Don't have to do anything */

				/* If tha category is bound to the edited product but the current product must be excluded */
				} elseif ( $is_cat_linked && ! $is_selected ) {

					$instance->add_to_temp( $type, $is_cat_linked, $key );

				/* If the category isn't bound, and it is needed to bind only some products from it */
				} elseif ( $is_selected ) {

					$instance->save_actions['product_add'][ $key ] = $instance->get_cat_id( $cat );

				/* If the category isn't bound, and it is additionally needed to unbind some products from it */
				} else {
					$instance->save_actions['product_remove'][] = $key;
				}

			/* if it is a category */
			} else {
				$action = $is_selected ? 'cat_add' : 'cat_remove';
				$instance->save_actions[ $action ][] = $node['id'];
			}
		}

		return $instance->handle_save_actions( $post_id, $type );
	}

	/**
	 * And an anchor to the end of list with products in order to further list parts loading
	 * @see self::get_products()
	 * @since  0.1
	 * @access public
	 * @static
	 * @param  string    $category      The taxonomy slug
	 * @param  int       $next_page     the next list par number
	 * @param  string    $position      The anchor position
	 */
	public static function get_anchor( $category, $next_page, $position ) {
		return array(
			'title'  => sprintf( '<span class="bkng_tree_loading">%s</span>', __( 'Loading', BWS_BKNG_TEXT_DOMAIN ) ),
			'key'    => 'bkng_more_anchor',
			'id'     => 'bkng_more_anchor',
			'icon'   => false,
			'cat'    => $category,
			'next_page'      => $next_page,
			'statusNodeType' => 'paging',
			'position'       => $position,
			'selected'       => true,
			'extraClasses'   => 'bkng_more_anchor_' . $position,
			'hideCheckbox'   => true,
			'unselectable'   => true
		);
	}

	/**
	 * Check whether to split list of products in parts(pages)
	 * @since  0.1
	 * @access public
	 * @static
	 * @param  int      $total_posts       Total amount of products in the category
	 * @param  int      $posts_per_page    Amount of products on the page
	 * @return boolean
	 */
	public static function is_paged( $total_posts, $posts_per_page ) {
		return $total_posts > $posts_per_page;
	}

	/**
	 * Checks whether the current list part(page) is the first one
	 * @since  0.1
	 * @access public
	 * @static
	 * @param  int      $loaded_page      The current page number
	 * @return boolean
	 */
	public static function is_first_page( $loaded_page ) {
		return $loaded_page <= 1;
	}

	/**
	 * Checks whether the current list part(page) is the last one
	 * @since  0.1
	 * @access public
	 * @static
	 * @param  int      $total_posts       Total amount of products in the category
	 * @param  int      $posts_per_page    Amount of products on the page
	 * @param  int      $loaded_page       The current page number
	 * @return boolean
	 */
	public static function is_last_page( $total_posts, $loaded_page, $posts_per_page ) {
		return ceil( $total_posts / $posts_per_page ) <= $loaded_page;
	}

	/**
	 * Checks whether the category is bind to the currently edited product
	 * @since  0.1
	 * @access public
	 * @param  string       $category  The category slug
	 * @param  int          $post_id   The ID of currently edited product
	 * @param  string       $type      The type of binding
	 * @return int|false               The category ID, false otherwise
	 */
	public function is_cat_linked( $category, $post_id, $type ) {
		$category = sanitize_title( $category );
		$post_id  = absint( $post_id );
		$type     = sanitize_title( $type );

		return
				is_null( $this->linked_cats_list ) && ! $this->set_linked_cats_list( $post_id, $type )
			?
				false
			:
				array_search( $category, $this->linked_cats_list );
	}

	/**
	 * Fetch the category ID
	 * @since  0.1
	 * @access public
	 * @param  string       $category  The category slug
	 * @return int
	 */
	public function get_cat_id( $category ) {
		$category = sanitize_title( $category );

		return
				is_null( $this->cats_list ) && ! $this->set_cats_list()
			?
				0
			:
				absint( array_search( $category, $this->cats_list ) );
	}

	/**
	 * Adds data to temporary storage
	 * @see    self::$temp
	 * @since  0.1
	 * @access public
	 * @param  string       $type        The type of binding
	 * @param  string       $cat_id      The category ID
	 * @param  int|string   $except_id   The product ID to be excluded from bining
	 * @return array                     The action result message
	 */
	public function add_to_temp( $type, $cat_id, $except_id ) {
		$type      = sanitize_title( $type );
		$cat_id    = sanitize_title( $cat_id );
		$except_id = absint( $except_id );

		if ( empty( $this->temp_storage[ $type ][ $cat_id ] ) )
			$this->temp_storage[ $type ][ $cat_id ] = array();

		$this->temp_storage[ $type ][ $cat_id ][] = $except_id;
	}

	/**
	 * Updates the binding data in database
	 * @since  0.1
	 * @access public
	 * @param  string       $post_id     The current edited product ID
	 * @param  string       $type        The type of binding
	 * @return array
	 */
	public function handle_save_actions( $post_id, $type ) {
		global $wpdb;
		$updated = $error = '';
		$post_id = absint( $post_id );
		$type    = sanitize_title( $type );
		$table   = BWS_BKNG_DB_PREFIX . 'linked_products';

		foreach ( $this->save_actions as $key => $data ) {

			if ( empty( $data ) )
				continue;

			switch ( $key ) {
				case 'cat_add':
					/* remove old records */
					$cat_ids = implode( ',', $data );
					$wpdb->query(
					    $wpdb->prepare(
                            "DELETE FROM `{$table}` WHERE `post_id`=%d AND `type`=%s AND `category_id` IN (%s);",
                            $post_id,
                            $type,
                            $cat_ids
                        )
                    );
					/* add new records */
					$values = array();
					foreach ( $data as $cat_id )
						$values[] = "({$post_id}, {$cat_id}, '{$type}')";
					$values = implode( ',', $values );
					$wpdb->query(
					    $wpdb->prepare(
                            "INSERT INTO `{$table}` (`post_id`, `category_id`, `type`) VALUES %s;",
                            $values
                        )
                    );
					break;
				case 'cat_remove':
					$cat_ids = implode( ',', $data );
					$wpdb->query(
					    $wpdb->prepare(
                            "DELETE FROM `{$table}` WHERE `post_id`=%d AND `type`=%s AND `category_id` IN (%s);",
                            $post_id,
                            $type,
                            $cat_ids
                        )
                    );
					break;
				case 'product_add':

					$values = $to_save_ids = array();
					$to_save_ids = array_keys( $data );
					$in          = implode( ',', $to_save_ids );
					$saved_ids   = array_flip( $wpdb->get_col(
					    $wpdb->prepare(
                            "SELECT `product_id` FROM `{$table}` WHERE `product_id` IN (%s) AND `post_id`=%d AND `type`=%s;",
                            $in,
                            $post_id,
                            $type
                        )
                    ) );

					/* get new unique values */
					$to_filter   = empty( $saved_ids ) ? $to_save_ids : array_diff( $to_save_ids, $saved_ids );
					$to_save_ids = array_unique( $to_filter );

					if ( empty( $to_save_ids ) ) {
						break;
					}

					foreach( $to_save_ids as $id )
						$values[] = "({$post_id}, {$id}, {$data[ $id ]}, '{$type}')";

					$values = implode( ',', $values );
					$wpdb->query(
					    $wpdb->prepare(
                            "INSERT INTO `{$table}` (`post_id`, `product_id`, `category_id`, `type`) VALUES %s;",
                            $values
                        )
                    );
					break;
				case 'product_remove':
					$ids = implode( ',', $data );
					$wpdb->query(
					    $wpdb->prepare(
                            "DELETE FROM `{$table}` WHERE `post_id`=%d AND `type`=%s AND `product_id` IN (%s);",
                            $post_id,
                            $type,
                            $ids
                        )
                    );
					break;
				default:
					break;
			}
		}

		$this->save_from_temp( $type, $post_id );

		if ( $wpdb->last_error )
			$error = $wpdb->last_error . '<br/>' . $wpdb->last_query;
		else
			$updated = __( 'Saved', BWS_BKNG_TEXT_DOMAIN );

		return array_filter( compact( 'error', 'updated' ) );
	}

	/**
	 * Fills the list of categories for further handling
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return boolean      The action result
	 */
	private function set_cats_list() {
		global $bws_bkng;
		$this->cats_list = array();
		$terms = $bws_bkng->get_terms( BWS_BKNG_CATEGORIES, array( 'hide_empty' => false ) );

		if ( is_wp_error( $terms ) || empty( $terms ) )
			return false;

		foreach ( $terms as $term )
			$this->cats_list[ $term->term_id ] = $term->slug;

		return true;
	}

	/**
	 * Fills the list of categories are bind to the cuurently edited product
	 * @since  0.1
	 * @access private
	 * @param  int          $post_id   The ID of currently edited product
	 * @param  string       $type      The type of binding
	 * @return boolean                The action result
	 */
	private function set_linked_cats_list( $post_id, $type ) {
		global $wpdb;

		$this->linked_cats_list = array();
		$table = BWS_BKNG_DB_PREFIX . 'linked_products';

		if ( is_null( $this->cats_list ) && ! $this->set_cats_list() )
			return false;

		$term_ids    = implode( ',', array_keys( $this->cats_list ) );
		$linked_cats = (array)$wpdb->get_col(
		    $wpdb->prepare(
                "SELECT DISTINCT `category_id`
                FROM `{$table}`
                WHERE `post_id`=%d
                    AND `category_id` IN (%s)
                    AND `product_id` IS NULL
                    AND `type`=%s;",
                $post_id,
                $term_ids,
                $type
            )
		);

		if ( empty( $linked_cats ) )
			return false;

		$this->linked_cats_list = array_intersect_key( $this->cats_list, array_flip( $linked_cats ) );

		return true;
	}

	/**
	 * Saves the data from the temporary storage
	 * @since  0.1
	 * @access private
	 * @param  int          $post_id   The ID of currently edited product
	 * @param  string       $type      The type of binding
	 * @return void
	 */
	private function save_from_temp( $type, $post_id ) {
		global $wpdb, $bws_bkng;

		if ( empty( $this->temp_storage[ $type ] ) )
			return;

		$table      = BWS_BKNG_DB_PREFIX . 'linked_products';
		$post_types = "'" . implode( "','", $bws_bkng->get_post_types() ) . "'";
		$limit      = 1000;
		foreach ( $this->temp_storage[ $type ] as $cat_id => $prod_ids ) {

			$offset     = 0;
			$except_ids = implode( ',', $prod_ids );

			while( $wpdb->query(
			    $wpdb->prepare(
                    "INSERT INTO `{$table}`
                    (`post_id`, `product_id`, `category_id`, `type`)
                    (SELECT {$post_id}, `p`.`ID`, {$cat_id}, '{$type}'
                     FROM `{$wpdb->posts}` AS `p`
                     LEFT JOIN `{$wpdb->prefix}term_relationships` AS `t`
                     ON `t`.`term_taxonomy_id`=%d
                        AND `t`.`object_id` NOT IN (%s)
                     WHERE `p`.`ID` = `t`.`object_id`
                        AND `p`.`post_type` IN (%s)
                        AND `p`.`post_status` = 'publish'
                     LIMIT %d, %d);",
                    $cat_id,
                    $except_ids,
                    $post_types,
                    $offset,
                    $limit
                )
			) ) {
				$offset += $limit;
			}
		}
	}
}