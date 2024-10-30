<?php /**
 * Contains the list of functions are used to handle products
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

/**
 * Modifies the ORDER BY parameter of the request to retrieve the list of products so that
 * the available products are displayed first.
 * The WP core automatically prescribes aliases for table names in a query when performing a JOIN,
 * and every time you need to select by the value of the post meta-field,
 * WP automatically sets an alias for the wp_postmeta table.
 * Therefore, in order to use the required alias in ORDER BY, you have to parse the entire request.
 * (filter hook on 'posts_orderby' won't help here)
 * @since    0.1
 * @param    string    $request     MySQL query string to get post data
 * @return   string    $request     MySQL query string to get post data
 */
if ( ! function_exists( 'bws_bkng_find_available_first' ) ) {
	function bws_bkng_find_available_first( $request ) {

		preg_match( "/(mt[\d+])\.meta_key[\s]?=[\s]?'bkng_product_status'/", $request, $matches );

		if ( empty( $matches[1] ) )
			return $request;

		return preg_replace( "/ORDER[\s]{1}BY/", "ORDER BY {$matches[1]}.meta_value='available' DESC, ", $request );
	}
}

if ( ! function_exists( 'bws_bkng_search_filter_join' ) ) {
	function bws_bkng_search_filter_join( $join ) {
		global $wpdb;
		if ( ! empty( $_GET['bws_bkng_action'] ) && 'search' == $_GET['bws_bkng_action'] ) {
			$query = bws_bkng_get_query();
			$join_fields_table = array();
			foreach( $query['search'] as $key => $value ) {
				switch( $key ) {
					case 'bws_bkng_location':
						$join .= 'LEFT JOIN `' . BWS_BKNG_DB_PREFIX . 'post_location` ON `' . $wpdb->posts .'`.`ID` = `' . BWS_BKNG_DB_PREFIX . 'post_location`.`post_id` ';
						$join .= 'LEFT JOIN `' . BWS_BKNG_DB_PREFIX . 'locations` ON `' . BWS_BKNG_DB_PREFIX . 'locations`.`location_id` = `' . BWS_BKNG_DB_PREFIX . 'post_location`.`location_id` ';
						break;
					case 'bws_bkng_price':
					case 'bws_bkng_adult':
					case 'bws_bkng_kid':
						if( ! isset( $join_fields_table[ $query['search']['bws_bkng_post_type'] ] ) || false === $join_fields_table[ $query['search']['bws_bkng_post_type'] ] ) {
							$join_fields_table[ $query['search']['bws_bkng_post_type'] ] = true;
							$join .= 'LEFT JOIN `' . BWS_BKNG_DB_PREFIX . $query['search']['bws_bkng_post_type'] . '_field_post_data` ON `' . BWS_BKNG_DB_PREFIX . $query['search']['bws_bkng_post_type'] . '_field_post_data`.`post_id` = `' . $wpdb->posts . '`.`ID` ';
							$join .= 'LEFT JOIN `' . BWS_BKNG_DB_PREFIX . $query['search']['bws_bkng_post_type'] . '_field_ids` ON `' . BWS_BKNG_DB_PREFIX . $query['search']['bws_bkng_post_type'] . '_field_post_data`.`field_id` = `' . BWS_BKNG_DB_PREFIX . $query['search']['bws_bkng_post_type'] . '_field_ids`.`field_id` ';
						}
						//$this->query_args['post_type'] = $value;
						break;
					case 'bws_bkng_from':
						//$this->query_args['post_type'] = $value;
						break;
					case 'bws_bkng_till':
						//$this->query_args['post_type'] = $value;
						break;
					case 'bws_bkng_accomodation':
						//$this->query_args['post_type'] = $value;
						break;
				}
			}
		}
		return $join;
	}
}

if ( ! function_exists( 'bws_bkng_search_filter_where' ) ) {
	function bws_bkng_search_filter_where( $where ) {
		global $wpdb, $bws_bkng;
		if ( ! empty( $_GET['bws_bkng_action'] ) && 'search' == $_GET['bws_bkng_action'] ) {
			$query = bws_bkng_get_query();
			foreach( $query['search'] as $key => $value ) {
				switch( $key ) {
					case 'bws_bkng_location':
						$where .= ' AND ( (  `location_address` LIKE "%' . $value . '%" ';
						$where .= ' AND `location_post_type` = "' . $query['search']['bws_bkng_post_type'] . '" ) ';
						if( 'bws_bike' == $query['search']['bws_bkng_post_type'] ) {
						    $like = '%' . $wpdb->esc_like( $value ) . '%';
							$find_location_bike = $wpdb->get_col(
                                $wpdb->prepare(
                                    'SELECT `post_id`
									FROM `' . BWS_BKNG_DB_PREFIX . 'post_location`
									LEFT JOIN `' . BWS_BKNG_DB_PREFIX . 'locations` ON `' . BWS_BKNG_DB_PREFIX . 'locations`.`location_id` = `' . BWS_BKNG_DB_PREFIX . 'post_location`.`location_id`									
									WHERE `location_address` LIKE %s
										AND `' . BWS_BKNG_DB_PREFIX . 'post_location`.`location_post_type` = "bws_bike"',
                                    $like
                                )
							);
							if( ! empty( $find_location_bike ) ) {
								$where .= ' OR `' . $wpdb->posts . '`.`ID` IN (' . implode( ',', $find_location_bike ) . ') ) ';
							}
						} else {
							$where .= ') ';
						}
						break;
					case 'bws_bkng_price':
						$price = explode( '-', $value );
						$where .= ' AND ( `' . BWS_BKNG_DB_PREFIX . $query['search']['bws_bkng_post_type'] . '_field_ids`.`field_slug` = "' . $bws_bkng->plugin_prefix . '_price" AND `' . BWS_BKNG_DB_PREFIX . $query['search']['bws_bkng_post_type'] . '_field_post_data`.`post_value` >= "' . $price[0] . '" AND `' . BWS_BKNG_DB_PREFIX . $query['search']['bws_bkng_post_type'] . '_field_post_data`.`post_value` <= "' . $price[1] . '" ) ';
						//$this->query_args['post_type'] = $value;
						break;
					case 'bws_bkng_from':
						//$this->query_args['post_type'] = $value;
						break;
					case 'bws_bkng_till':
						//$this->query_args['post_type'] = $value;
						break;
					case 'bws_bkng_adult':
						//$this->query_args['post_type'] = $value;
						break;
					case 'bws_bkng_kid':
						//$this->query_args['post_type'] = $value;
						break;
					case 'bws_bkng_accomodation':
						//$this->query_args['post_type'] = $value;
						break;
				}
			}
		}
		return $where;
	}
}

if ( ! function_exists( 'bws_bkng_search_filter_groupby' ) ) {
	function bws_bkng_search_filter_groupby( $groupby ) {
		global $wpdb;
		if ( ! empty( $_GET['bws_bkng_action'] ) && 'search' == $_GET['bws_bkng_action'] ) {
			$query = bws_bkng_get_query();
			foreach( $query['search'] as $key => $value ) {
				switch( $key ) {
					case 'bws_bkng_price':
					case 'bws_bkng_from':
					case 'bws_bkng_till':
					case 'bws_bkng_adult':
					case 'bws_bkng_kid':
						$groupby = ' `' . $wpdb->posts . '`.`ID` ';
						break;
					case 'bws_bkng_accomodation':
						//$this->query_args['post_type'] = $value;
						break;
				}
			}
		}
		return $groupby;
	}
}

/**
 * Gets all products from the database according to the required request
 * @since    0.1
 * @param    void
 * @return   array     $products      The list with WP_POST objects
 */
if ( ! function_exists( 'bws_bkng_query_products' ) ) {
	function bws_bkng_query_products() {
		global $wpdb;
		global $wp_query;
		add_filter( 'posts_join', 'bws_bkng_search_filter_join' );
		add_filter( 'posts_where', 'bws_bkng_search_filter_where' );
		add_filter( 'posts_groupby', 'bws_bkng_search_filter_groupby' );
		//add_filter( 'posts_request', 'bws_bkng_find_available_first' );
		/*switch( $key ) {
				case 'bws_bkng_post_type':
					$this->query_args['post_type'] = $value;
					break;
				case 'bws_bkng_location':
					$find_location_hotel = $wpdb->get_col(
						'SELECT `post_id`
							FROM `' . BWS_BKNG_DB_PREFIX . 'locations`, `' . BWS_BKNG_DB_PREFIX . 'post_location`
							WHERE `' . BWS_BKNG_DB_PREFIX . 'locations`.`location_id` = `' . BWS_BKNG_DB_PREFIX . 'post_location`.`location_id`
								AND `location_address` LIKE "%' . $value . '%"
								AND `location_post_type` = "bws_hotel"'
					);
					$find_location_room = $wpdb->get_col(
						'SELECT `post_id`
							FROM `' . BWS_BKNG_DB_PREFIX . 'locations`, `' . BWS_BKNG_DB_PREFIX . 'post_location`
							WHERE `' . BWS_BKNG_DB_PREFIX . 'locations`.`location_id` = `' . BWS_BKNG_DB_PREFIX . 'post_location`.`location_id`
								AND `location_address` LIKE "%' . $value . '%"
								AND `location_post_type` = "bws_room"'
					);
					if( ! empty( $find_location_hotel ) ) {
						//$this->query_args['post__in'] = $find_location;
					}
					if( ! empty( $find_location_room ) ) {
						$this->query_args['post__in'] = $find_location;
					}
					break;
				case 'bws_bkng_price':
					//$this->query_args['post_type'] = $value;
					break;
				case 'bws_bkng_from':
					//$this->query_args['post_type'] = $value;
					break;
				case 'bws_bkng_till':
					//$this->query_args['post_type'] = $value;
					break;
				case 'bws_bkng_adult':
					//$this->query_args['post_type'] = $value;
					break;
				case 'bws_bkng_kid':
					//$this->query_args['post_type'] = $value;
					break;
				case 'bws_bkng_accomodation':
					//$this->query_args['post_type'] = $value;
					break;
			}
		*/
		$products = query_posts( bws_bkng_get_query() );
		remove_filter( 'posts_join', 'bws_bkng_search_filter_join' );
		remove_filter( 'posts_where', 'bws_bkng_search_filter_where' );
		remove_filter( 'posts_where', 'bws_bkng_search_filter_groupby' );
		//remove_filter( 'posts_request', 'bws_bkng_find_available_first' );
		return $products;
	}
}

/**
 * Gets all attached products
 * @since    0.1
 * @param    mixed       $post           Curren post data
 * @param    string      $type           Linked product conjunction type
 * @param    boolean     $group_by_cats
 * @return   array                       Format:
 *                                        array(
  'linked_products'         => @var array|false   The list of posts which were linked to the current, false otherwise
  'show_rent_interval_form' => @var boolean       Whether to show the rent interval form in the
 */
if ( ! function_exists( 'bws_bkng_query_linked_products' ) ) {
	function bws_bkng_query_linked_products( $post = null, $type = 'extra', $group_by_cats = true ) {
		global $wpdb, $bws_bkng;

		$post = get_post( $post );

		if ( empty( $post->ID ) )
			return false;

		$type = esc_sql( $type );
		$linked_products = BWS_BKNG_DB_PREFIX . "linked_products";

		/**
		 * I'm not sure about the correctness of the request,
		 * @todo test and possibly fix
		 */
		$ids = $wpdb->get_col(
			"SELECT `product_id`
			FROM `{$linked_products}`
			WHERE `product_id` IS NOT NULL
				AND `post_id`={$post->ID}
				AND `type`='{$type}'
				AND `product_id`<>{$post->ID}
				AND `product_id` NOT IN (SELECT `ID` FROM `{$wpdb->posts}` WHERE `post_parent`={$post->ID})
			UNION
			SELECT `object_id`
			FROM `{$wpdb->prefix}term_relationships`
			WHERE `term_taxonomy_id` IN (
					SELECT `category_id`
					FROM `{$linked_products}`
					WHERE `product_id` IS NULL
						AND `post_id`={$post->ID}
						AND `type`='{$type}'
				) AND `object_id`<>{$post->ID}
				AND `object_id` NOT IN (SELECT `ID` FROM `{$wpdb->posts}` WHERE `post_parent`={$post->ID});"
		);

		if ( empty( $ids ) || $wpdb->last_error )
			return false;

		$args = array(
			'orderby'          => 'title',
			'posts_per_page'   => -1,
			'post__in'         => $ids,
			'post_type'        => $bws_bkng->get_post_types(),
			'post_status'      => array( 'publish', 'private' ),
			'suppress_filters' => false,
			'meta_query'       => array(
				array(
					'key'     => "bkng_product_status",
					'value'   => 'available'
				)
			)
		);

		$products = get_posts( $args );

		if ( ! $group_by_cats )
			return $products;

		$cat_list = BWS_BKNG_List_Categories::get_instance();
		$cat_list = $cat_list->get_all_categories();
		$cat_ids  = array_keys( $cat_list );
		$linked_products         = array();

		if ( empty( $cat_list ) )
			return $products;

		foreach ( $products as $product ) {

			if ( empty( $product->bkng_category_id ) || ! in_array( $product->bkng_category_id, $cat_ids ) )
				continue;

			$cat_id = $product->bkng_category_id;

			if ( empty( $linked_products[ $cat_id ] ) ) {
				$linked_products[ $cat_id ] = array(
					'cat_data' => $cat_list[ $cat_id ],
					'products' => array()
				);
			}

			$linked_products[ $cat_id ]['products'][] = $product;
		}

		return $linked_products;

	}
}

if ( ! function_exists( 'bws_bkng_is_countable' ) ) {
	function bws_bkng_is_countable( $post = null ) {
		$post = get_post( $post );

		return empty( $post->is_countable ) ? !! get_post_meta( $post->ID, 'bkng_quantity_available', true ) : !! $post->is_countable;
	}
}

if ( ! function_exists( 'bws_bkng_get_max_quantity' ) ) {
	function bws_bkng_get_max_quantity( $post = null ) {

		$post = get_post( $post );

		return isset( $post->bkng_in_stock ) ? $post->bkng_in_stock : absint( get_post_meta( $post->ID, 'bkng_in_stock', true ) );
	}
}


/**
 * Checks the display of the product list
 * @since    0.1
 * @param    void
 * @return   boolean
 */
if ( ! function_exists( 'bws_bkng_is_list_view' ) ) {
	function bws_bkng_is_list_view() {
		$query = bws_bkng_get_query();
		return 'list' === $query['view'];
	}
}

/**
 * Fetch the product rent interval
 * @since    0.1
 * @param    WP_Post|int|null
 * @param    string           $return
 * @return   string/false     String  - if the rent interval is different to 'none'
 */
if ( ! function_exists( 'bws_bkng_get_rent_interval' ) ) {
	function bws_bkng_get_rent_interval( $post = null, $return = "label" ) {
		global $bws_bkng;

		$post = get_post( $post );

		if ( empty( $post->ID ) )
			return false;

		$slug = $bws_bkng->get_product_rent_interval( $post->ID );

		switch( $slug ) {
			case 'none':
				return false;
			case false:
				$slug = 'day';
				break;
			default:
				break;
		}

		return '<div class="bws_bkng_product_interval_row"><span class="bws_bkng_product_interval_column">/</span><span class="bws_bkng_product_interval_column">' . $bws_bkng->get_rent_interval( $slug, $return ) . '</span></div>';
	}
}

/**
 * Fetch the product  min. rent interval step
 * @since    0.1
 * @param    WP_Post|int|null
 * @return   int/false     A number of seconds - rent interval, false otherwise
 */
if ( ! function_exists( 'bws_bkng_get_rent_interval_step' ) ) {
	function bws_bkng_get_rent_interval_step( $post = null ) {
		global $bws_bkng;

		$post = get_post( $post );

		if ( empty( $post->ID ) )
			return false;

		$interval_slug = $bws_bkng->get_product_rent_interval( $post->ID );
		return $bws_bkng->get_rent_interval( $interval_slug, 'number' );
	}
}

/**
 * Outputs classes for a list of products
 * @since    0.1
 * @param    void
 * @return   void
 */
if ( ! function_exists( 'bws_bkng_products_list_class' ) ) {
	function bws_bkng_products_list_class() {

		$classes = array( 'bws_bkng_products_list' );

		$classes[] = bws_bkng_is_list_view() ? 'bws_bkng_list_view' : 'bws_bkng_grid_view';

		$classes = join( ' ', apply_filters( 'bws_bkng_products_list_class', $classes ) );
        echo 'class="' . $classes . '"';
	}
}

/**
 * Gets additional HTML attributes for each product
 * @since    0.1
 * @param    WP_Post|int|null
 * @return   string|null       the content of attribute 'class'
 */
if ( ! function_exists( 'bws_bkng_get_product_classes' ) ) {
	function bws_bkng_get_product_classes( $post = null ) {

		$post = get_post( $post );

		if ( empty( $post->ID ) ) {
			echo '';
			return null;
		}

		$attributes = 'class="%1$s"';

		$classes = array( 'bws_bkng_product', "bws_bkng_product_{$post->ID}" );

		$classes[] = esc_attr( 'bws_bkng_' . bws_bkng_get_product_status() );
		$classes[] = esc_attr( 'bws_bkng_product_' . get_post_status() );

		$classes = join( ' ', apply_filters( 'bws_bkng_product_class', $classes ) );

		return sprintf( $attributes, $classes );
	}
}

/**
 * Outputs additional HTML attributes for each product
 * @since    0.1
 * @param    WP_Post|int|null
 * @return   void
 */
if ( ! function_exists( 'bws_bkng_product_classes' ) ) {
	function bws_bkng_product_classes( $post = null ) {
		echo bws_bkng_get_product_classes( $post );
	}
}

/**
 * Outputs product title
 * @since    0.1
 * @param    WP_Post|int|null
 * @return   void
 */
if ( ! function_exists( 'bws_bkng_product_excerpt' ) ) {
	function bws_bkng_product_excerpt( $post = null ) {
		global $bws_bkng;

		$post = get_post( $post );

		if ( empty( $post->ID ) ) {
			echo '';
			return;
		}

		if ( $bws_bkng->allow_variations && BWS_BKNG_VARIATION == $post->post_type && ! empty( $post->post_parent ) )
			echo get_the_excerpt( $post->post_parent );
		else
			the_excerpt();
	}
}

/**
 * Gets the price of a product
 * @since    0.1
 * @param    WP_Post|int|null
 * @return   string                  Product price
 */
if ( ! function_exists( 'bws_bkng_get_product_price' ) ) {
	function bws_bkng_get_product_price( $post = null ) {
		global $bws_bkng, $wpdb;

		$post = get_post( $post );

		if ( empty( $post->ID ) )
			return false;

		if( NULL !== $price_field_id = $wpdb->get_var( $wpdb->prepare( 'SELECT `field_id` FROM `' . BWS_BKNG_DB_PREFIX . $post->post_type . '_field_ids` WHERE `field_slug` = %s', $bws_bkng->plugin_prefix . '_price' ) ) ) {
			$price = $wpdb->get_var( $wpdb->prepare( 'SELECT `post_value` FROM `' . BWS_BKNG_DB_PREFIX . $post->post_type . '_field_post_data` WHERE `field_id` = %d AND `post_id` = %d' , $price_field_id, $post->ID ) );
		} else {
			return false;
		}

		/* Filter for different price types ( basic, seasons, by_days )*/
		$price = apply_filters( 'bws_bkng_product_price', $price, $post );

		return abs( floatval( $price ) );
	}
}

/**
 *
 *  Filters the price of the product depending on the type of price ( basic ( default price and price by days ), seasons )
 * @since    1.0.0
 * @param    mixed    $post         Curren post data
 * @param    int      $price        Price of current product
 * @return   int
 */
if ( ! function_exists( 'bws_bkng_product_price_filter' ) ) {
	function bws_bkng_product_price_filter( $price, $post ) {
		global $bws_bkng, $wpdb;
		/**
		 * A lot of commented code because this functionality is not expexted in rent a bike theme but needs refactoring for bike rental plugin structure
		 */
		$prefix = $bws_bkng->plugin_prefix . '_';
		$fields = array(
			'price_by_days',
			'on_price_by_days',
			'price_by_seasons',
		);
		$fields = "'" . $prefix . implode( "', '" . $prefix, $fields ) . "'";

		$fields_data = $wpdb->get_results(
			"SELECT `field_id`, `field_slug`
			FROM `" . BWS_BKNG_DB_PREFIX . $post->post_type . "_field_ids`
			WHERE `visible_status` = 0
				AND `field_slug` IN ( " . $fields . " )
			ORDER BY `field_id` ASC",
			ARRAY_A
		);

		$values = array();
		foreach ( $fields_data as $field ) {
			$db_value = $wpdb->get_var( $wpdb->prepare( 'SELECT `post_value` FROM `' . BWS_BKNG_DB_PREFIX . $post->post_type . '_field_post_data` WHERE `field_id` = %d AND `post_id` = %d', $field['field_id'], $post->ID ) );
			$values[ $field['field_slug'] ] = empty( $db_value ) ? '' : $db_value;
		}

		/* price by days */
		$price_type = $bws_bkng->get_option( 'price_type' );
		$bkng_on_price_by_days = isset( $values[ $prefix . 'on_price_by_days' ] ) ? $values[ $prefix . 'on_price_by_days' ] : false;

		/* set the average seasonal price if the rent range is outside the season */
		$rent_intervals_list = $bws_bkng->get_rent_interval();

		/* set default price if :
		    1) daily price off + price_type != seasons
		    2) rent_interval == none
		*/
		if ( ! $bkng_on_price_by_days && 'seasons' != $price_type ) {
			return $price;
		}

		/* start and end of interval rent subject to option rent_interval */

		$rent_interval_session = bws_bkng_get_session_rent_interval();

		$date_from = $rent_interval_session['from'];
		$date_till = $rent_interval_session['till'];

		$rent_interval = bws_bkng_get_query();

		if ( isset( $rent_interval['search'] ) ) {
			$date_from = ( is_string( $rent_interval['search']['from'] ) ) ? strtotime( $rent_interval['search']['from'] ) : $rent_interval['search']['from'] ;
			$date_till = ( is_string( $rent_interval['search']['till'] ) ) ? strtotime( $rent_interval['search']['till'] ) : $rent_interval['search']['till'] ;;
        }

        /* nums of rent intervals */
        $num_rent_intervals = ceil( ( $date_till - $date_from ) / DAY_IN_SECONDS );

        /* rental period rounding */
        $date_till += ( $num_rent_intervals - ( ( $date_till - $date_from ) / DAY_IN_SECONDS ) ) * DAY_IN_SECONDS;
		/* get the selected interval in days */
        switch ( $price_type ) {
            case 'seasons':
                /* set default price if renting for a year is selected  */
                if ( isset( $bkng_rental_interval ) && 'year' == $bkng_rental_interval  ) {
	                return $price;
                }

	            /* price by seasons */
                $price_seasons = isset( $values[ $prefix . 'price_by_seasons' ] ) ? maybe_unserialize( $values[ $prefix . 'price_by_seasons' ] ) : false;
                $result_price = array();

                if ( ! empty( $price_seasons ) ) {

                    $date_from_season = explode( '-', date("Y-m", $date_from) );
                    $date_till_season = explode( '-', date('Y-m', $date_till) );

	                $date_from_season = array( 'year' => $date_from_season[0], 'month' => $date_from_season[1] );
	                $date_till_season = array( 'year' => $date_till_season[0], 'month' => $date_till_season[1] );;

	                $current_year_date_from = $date_from_season['year'];
	                $current_year_date_till = $date_till_season['year'];

	                $rental_years_list = range( $current_year_date_from, $current_year_date_till );

	                /* forming list of months by years */
	                $rental_month_list = array();

                    if ( $current_year_date_from == $current_year_date_till ) {
	                    $rental_month_list[$current_year_date_from] = range( $date_from_season['month'], $date_till_season['month'] );
                    } else {
	                    foreach ( $rental_years_list as $year ) {
		                    /* start rental year */
		                    if ( $year == $current_year_date_from ) {
			                    $rental_month_list[$year] = range( $date_from_season['month'], 12 );
                            /* end rental year */
		                    } else if ( $year == $current_year_date_till ) {
			                    $rental_month_list[$year] = range( 1, $date_till_season['month'] );
                            /* intermediate rental years */
		                    } else {
			                    $rental_month_list[$year] = range( 1, 12 );
		                    }
	                    }
                    }

                    $range = array(
                        'winter' => array( 12, 1, 2 ),
                        'spring' => array( 3, 4, 5 ),
                        'summer' => array( 6, 7 ,8 ),
                        'autumn' => array( 9, 10, 11),
                    );

	                $times  = array();

	                /* forming timestamp for rental months */
	                foreach ( $rental_month_list as $year => $months ) {
                        foreach ( $months as $month ) {
	                        $first_minute = mktime(0, 0, 0, $month, 1, $year );
	                        $last_minute = mktime(23, 59, 59, $month, date('t', $first_minute), $year );
	                        $times[$year][$month] = array($first_minute, $last_minute);
                        }
                    }

	                $result_month_list = array();

	                /* rental period by month array( 'month' => array( rental month of season ) ) */
	                foreach ( $range as $season_name => $month_nums ) {
	                    foreach ( $rental_month_list as $year => $months ) {
		                    $rent_months = array_intersect( $month_nums, (array)$months );
		                    if ( ! empty( $rent_months ) ) {
			                    $result_month_list[ $year ][ $season_name ] = $rent_months;
		                    }
	                    }
	                }

	                /* set the base seasonal price if the rent range is not outside the season */
                    foreach ( $range as $season_name => $month_nums ) {

                        if ( in_array( $date_from_season['month'], $month_nums ) &&
                             in_array( $date_till_season['month'], $month_nums ) &&
                             ( $date_from_season['year'] == $date_till_season['year'] ) ) {

                            $price = $price_seasons[$season_name];
                            $is_rent_interval_one_season = true;
                            break;
                        }
                    }

                    if ( ! isset( $is_rent_interval_one_season ) ) {
                        foreach ( $result_month_list as $year => $season_list ) {
	                        foreach ( (array)$season_list as $season => $month_list ) {

		                        /* Functionality is needed to determine the rental time by seasons
                                 * determine the final month of the season : start_month - [$range[$season][0], end_month - [$range[$season][2]
                                 * timestamp final month of the season : start month - $times[$year][ id final month of the season ][0], end month- $times[$year][ id final month of the season ][1] */

		                        $date_from_month_season = intval( $date_from_season['month'] );
		                        $date_till_month_season = intval( $date_till_season['month'] );

		                        /* start season */
		                        if ( ( $year == $date_from_season['year'] ) && in_array( $date_from_month_season, $month_list ) ) {
		                            /* number of rental days per season */
			                        $timestamp_end_season   = isset( $times[$year][$range[$season][2]][1] ) ? $times[$year][$range[$season][2]][1] : $times[$year][$range[$season][0]][1];
		                            $result_price[$year][ $season ] = ( $timestamp_end_season - $date_from ) / DAY_IN_SECONDS;

                                /* end season */
		                        } else if ( ( $year == $date_till_season['year'] ) && in_array( $date_till_month_season, $month_list ) ) {
			                        /* number of rental days per season */
			                        $timestamp_start_season = isset( $times[$year][$range[$season][0]][0] ) ? $times[$year][$range[$season][0]][0] : $times[$year][$range[$season][1]][0];
		                            $result_price[$year][ $season ] = ( $date_till - $timestamp_start_season ) / DAY_IN_SECONDS;

                                /* intermediate seasons */
		                        } else {
		                            /* number of rental days per season */
			                        $timestamp_start_season = isset( $times[$year][$range[$season][0]][0] ) ? $times[$year][$range[$season][0]][0] : $times[$year][$range[$season][1]][0];
			                        $timestamp_end_season   = isset( $times[$year][$range[$season][2]][1] ) ? $times[$year][$range[$season][2]][1] : $times[$year][$range[$season][0]][1];
                                    $result_price[$year][$season] = ( $timestamp_end_season - $timestamp_start_season ) / DAY_IN_SECONDS;
		                        }
	                        }
                        }
                    }

                    /* calculation of the average price for current rent_interval */
	                if ( ! empty( $result_price ) ) {
		                $sum_price_by_seasons = 0;

                        /* summation prices by seasons */
                        foreach ( $result_price as $year => $seasons ) {
			                foreach ( $seasons as $season_name => $rent_period ) {
				                $sum_price_by_seasons += ( $price_seasons[$season_name] * $rent_period );
			                }
                        }

                        $price = ceil( $sum_price_by_seasons / $num_rent_intervals );
                    }
                }
                break;
            case 'basic':
	            /* data format in post_meta : row_inputs_id => array( price => 100, day_from => 1, day_to => 2 ) */
	            $bkng_price_by_days = isset( $values[ $prefix . 'price_by_days' ] ) ? maybe_unserialize( $values[ $prefix . 'price_by_days' ] ) : false;

				/* set standart price for rental period of less than one day  */
	            if ( ( $date_till - $date_from ) < DAY_IN_SECONDS ) {
                    return $price;
                }

	            /* get the number of days */
				$basic_rent_interval = ( $date_till - $date_from ) / DAY_IN_SECONDS;

                foreach ( $bkng_price_by_days as $input_index => $bkng_product_meta ) {
		            if ( $basic_rent_interval >= $bkng_product_meta['day_from'] && $basic_rent_interval <= $bkng_product_meta['day_to'] ) {
						$price = $bkng_product_meta['price'];
			            break;
		            }
	            }
	            break;
        }

		return $price;
	}
}

if ( ! function_exists( 'bws_bkng_show_product_price' ) ) {
	function bws_bkng_show_product_price( $post = null ) {
		global $bws_bkng;

		$post = get_post( $post );

		if ( empty( $post->ID ) )
			return false;

		return empty( $post->bkng_price_on_request );
	}
}

if ( ! function_exists( 'bws_bkng_product_price' ) ) {
    /**
     * Outputs product price
     * @since    0.1
     * @param null $post
     * @return false|void
     */
	function bws_bkng_product_price( $post = null ) {

		$post = get_post( $post );

		if ( empty( $post->ID ) )
			return false;

		$price = bws_bkng_get_product_price( $post );
		if ( $price ) {
			$interval = bws_bkng_get_rent_interval();
			$before   = strval( apply_filters( 'bws_bkng_before_price', '' ) );
			$after    = strval( apply_filters( 'bws_bkng_after_price',  '' ) );
			$price    = bws_bkng_price_format( $price );
			echo "{$before} {$price} {$interval} {$after}";
		} else {
			do_action( 'bws_bkng_no_price_notice' );
		}
	}
}

if ( ! function_exists( 'bws_bkng_product_conditional_prices' ) ) {
	function bws_bkng_product_conditional_prices( $post_id = null ) {
		global $bws_bkng;
		
		$post_data = new BWS_BKNG_Post_Data( $post_id );
		$fields = array(
			'on_price_by_days',
			'price_by_days',
			'price_by_seasons',
		);

		foreach ( $fields as $field ) {
			$data[ $field ] = $post_data->get_attribute( $field );
		}

		if ( !! $data['on_price_by_days'] && ! empty( $data['price_by_days'] ) ) {
			return maybe_unserialize( $data['price_by_days'] );
		} elseif ( ! empty( $data['price_by_seasons'] ) ) {
			return maybe_unserialize( $data['price_by_seasons'] );
		}

		return false;
	}
}

/**
 * Fetch the data of the product category
 * @since    0.1
 * @param    mixed         $post  Curren post data
 * @return   object|false         An instance of WP_Term class if the category was founded, false otherwises
 */
if ( ! function_exists( 'bws_bkng_get_product_category' ) ) {
	function bws_bkng_get_product_category( $post = null ) {
		global $bws_bkng;
		return $bws_bkng->get_product_category( '' , $post );
	}
}

/**
 * Fetch the list of poduct tags
 * @since    0.1
 * @param    mixed         $post  Curren post data
 * @return   object|false         WP_Term object - if all is OK, false otherise
 */
if ( ! function_exists( 'bws_bkng_get_product_tags' ) ) {
	function bws_bkng_get_product_tags( $post = null ) {

		$post = get_post( $post );

		if ( empty( $post->ID ) )
			return false;

		$tags = wp_get_post_terms( $post->ID, BWS_BKNG_TAGS );

		return is_wp_error( $tags ) ? false : $tags;

	}
}

/**
 * Get product status
 * @since    0.1
 * @param   mixed             $post   Curren post data
 * @return  string|boolean            The product status or false in case if get product status is impossible
 */
if ( ! function_exists( 'bws_bkng_get_product_status' ) ) {
	function bws_bkng_get_product_status( $post = null ) {
		$post = get_post( $post );

		if ( empty( $post->ID ) ) {
			return false;
		}

		$product_post_data	= new BWS_BKNG_Post_Data( $post->ID );

		$status = $product_post_data->get_attribute( 'statuses' );

		if ( 'available' == $status && bws_bkng_is_countable( $post->ID ) && ! bws_bkng_get_max_quantity() ) {
			$status = 'not_available';
		}

		return $status;
	}
}

if ( ! function_exists( 'bws_bkng_get_product_address' ) ) {
	function bws_bkng_get_product_address( $post = null ) {
		global $wpdb;

		$post = get_post( $post );

		if ( empty( $post->ID ) ) {
			return false;
		}
		$post_location = $wpdb->get_var( $wpdb->prepare( '
			SELECT `location_address` FROM `' . BWS_BKNG_DB_PREFIX . 'locations`
			INNER JOIN `' . BWS_BKNG_DB_PREFIX . 'post_location` ON `' . BWS_BKNG_DB_PREFIX . 'locations`.`location_id` = `' . BWS_BKNG_DB_PREFIX . 'post_location`.`location_id`
			WHERE `' . BWS_BKNG_DB_PREFIX . 'post_location`.`post_id` = %d
		', $post->ID ) );

		return $post_location;
	}
}

/**
 * Displays a note about product status
 * @since    0.1
 * @param   void
 * @return  void
 */
if ( ! function_exists( 'bws_bkng_product_status_notice' ) ) {
	function bws_bkng_product_status_notice( $status = '' ) {
		global $bws_bkng;

		$post_status = empty( $status ) ? bws_bkng_get_product_status() : $status;

		$statuses = $bws_bkng->get_option( 'products_statuses' );

		if ( empty( $statuses[ $post_status ]['title'] ) )
			return; ?>

		<div class="bws_bkng_status_notice bws_bkng_<?php echo esc_attr( $post_status ); ?>_notice"><?php echo esc_html( $statuses[ $post_status ]['title'] ); ?></div>

	<?php }
}

/**
 * Checks whether the current product in the cart
 * @since    0.1
 * @param    WP_Post|int|null
 * @return   boolean
 */
if ( ! function_exists( 'bws_bkng_product_is_in_cart' ) ) {
	function bws_bkng_product_is_in_cart( $post = null ) {
		$post = get_post( $post );

		if ( empty( $post->ID ) )
			return false;

		$cart = BWS_BKNG_Cart::get_instance();
		return $cart->is_in_cart( $post->ID );
	}
}

/* filter price function */
add_filter( 'bws_bkng_product_price', 'bws_bkng_product_price_filter', 10, 2 );