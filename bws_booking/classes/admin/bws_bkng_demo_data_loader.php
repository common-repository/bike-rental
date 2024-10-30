<?php
/**
 * Contains the functionality to handle demo-data.
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Demo_Data_Loader' ) )
	return;

class BWS_BKNG_Demo_Data_Loader {

	/**
	 * Contains an instance of the current class
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    object
	 */
	private static $instance;

	/**
	 * The name of the DB option that contains the installed demo items list.
	 * Also, it is used to initialize the hook name in order to add necessary demo-data from the outside of Booking core
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    string
	 */
	private $hook_slug;

	/**
	 * Contains the demo options
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    array
	 */
	private $option;

	/**
	 * Contains the list of items that need to be handled after the main process of the installation
	 * @uses   During demo data installation
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    array
	 */
	private $temp;

	/**
	 * Contains the list of errors that may be occurrred during the installation/removing of demo-data
	 * @since  0.1
	 * @access private
	 * @static
	 * @var    object
	 */
	private $errors;

	/**
	 * Fetch the class instance
	 * @since    0.1
	 * @access   public
	 * @static
	 * @param    boolean   $force
	 * @return   object    An instance of the current class
	 */
	public static function get_instance( $force = false ) {

		if ( ! self::$instance instanceof self || $force )
			self::$instance = new self();

		return self::$instance;
	}

	/**
	 * Checks wether the demo-data is installed
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return boolean
	 */
	public function is_demo_installed() {
		return !! $this->option;
	}

	/**
	 * Inits the demo-data installation
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void|bool
	 */
	public function install() {
		global $bws_bkng, $wpdb;
		$data = array_filter( $this->get_data() );

		if ( empty( $data ) )
			return false;

		$error_code = apply_filters( 'bws_bkng_prevent_demo_loading', false );
		if ( $error_code ) {
			$this->add_error( $error_code );
			return false;
		}

		/**
		 * Add products' categories
		 */
		if ( ! empty( $data['categories'] ) ) {
			foreach( $data['categories'] as $taxonomy => $term_values ) {
				foreach( $term_values as $term_value ) {
					$this->add_term( $term_value, $taxonomy );
				}
			}
		}

		/**
		 * Add products' additional attributes
		 */
		if ( ! empty( $data['attributes'] ) ) {
			foreach( $data['attributes'] as $post_type => $attributes ) { // BWS_BKNG_DB_PREFIX . $post_type . '_field_ids`
				foreach( $attributes as $attribute ) {
					if( 0 == $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_ids` WHERE `field_slug` = %s', $attribute['slug'] ) ) ) {
						$query = $wpdb->prepare( 'INSERT IGNORE INTO `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_ids`
							SET `field_name` = %s,
								`field_slug` = %s,
								`description` = %s,
								`field_type_id` = %d,
								`visible_status` = 1;',
						    $attribute['name'],
                            $attribute['slug'],
                            $attribute['desc'],
                            $attribute['type']
                        );
						$result = $wpdb->query( $query );
						if( empty( $wpdb->last_error ) && 0 < $result ) {
							$attribute['id'] = $wpdb->insert_id;
							$this->add_to_temp( 'attributes', $post_type, $attribute['id'], $attribute['id'] );
							if( ! empty( $attribute['value'] ) ) {
								foreach( $attribute['value'] as $key => $attribute_value ) { //BWS_BKNG_DB_PREFIX . $post_type . '_field_values`
									$query = $wpdb->prepare( 'INSERT IGNORE INTO `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_values`
										SET `field_id` = %d,
											`value_name` = %s,
											`order` = %d;',
									    $attribute['id'],
                                        $attribute_value,
                                        $key
                                    );
									$wpdb->query( $query );
									$attribute_value_id = $wpdb->insert_id;
									$this->add_to_temp( 'attribute_values', $post_type, $attribute_value_id, $attribute_value_id );
								}
							}
						}
					}
				}
			}
		}

		/**
		 * Add location
		 */
		if ( ! empty( $data['locations'] ) ) {
			foreach( $data['locations'] as $post_type => $value ) {
				foreach ($value as $locations) {
					if( 0 == $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM `' . BWS_BKNG_DB_PREFIX . 'locations` WHERE `location_name` = %s', $locations['location_name'] ) ) ) {
						$query = $wpdb->prepare(
						    'INSERT IGNORE INTO `' . BWS_BKNG_DB_PREFIX . 'locations`
							SET `location_name` = %s,
								`location_address` = %s,
								`location_latitude` = %f,
								`location_longitude` = %f',
						    $locations['location_name'],
                            $locations['location_address'],
                            $locations['location_latitude'],
                            $locations['location_longitude']
                        );
						$result = $wpdb->query( $query );
						if( empty( $wpdb->last_error ) && 0 < $result ) {
							$locations['id'] = $wpdb->insert_id;

							$this->add_to_temp( 'value_locations', $locations['id'], $locations['id']);

						}
					}
				}
			}
		}

		/**
		 * Add products (or may be other post, pages, etc.)
		 */
		if ( ! empty( $data['posts'] ) ) {
            $posts = [];
			foreach( $data['posts'] as $post_type => $post_data_array  ) {
				foreach( $post_data_array as $post_data ) {
					if ( apply_filters( 'bws_bkng_prevent_demo_loading', false ) ) {
						continue;
					}

					$post_id = wp_insert_post( $post_data );

                    $post = [
                        'ID' => $post_id,
                        'post_type' => $post_type,
                        'related_post_type' => null,
                    ];

					if ( empty( $post_id ) || is_wp_error( $post_id ) ) {
						$this->add_error( 'not_installed_post', $post_data['post_title'] );
						continue;
					}

                    if ( ! empty( $post_data['thumbnail'] ) ) {
						$this->set_thumbnail( $post_data['thumbnail'], $post_id );
					}

                    if ( ! empty( $post_data['gallery'] ) ) {
						$gallery_ids = array();
						foreach( $post_data['gallery'] as $gallery_image ) {
							$gallery_ids[] = $this->set_gallery_image( $gallery_image );
						}
						update_post_meta( $post_id, 'bws_bkng_products_images', implode( ',', $gallery_ids ) );
					}

					if ( ! empty( $post_data['terms'] ) ) {
						foreach( $post_data['terms'] as $taxonomy => $terms ) {
							$term_ids = wp_set_object_terms( $post_id, $terms, $taxonomy );
						}
					}

					if ( ! empty( $post_data['attributes'] ) ) {
						foreach( $post_data['attributes'] as $attribute_slug => $attribute_values ) {
							$attribute_id = $wpdb->get_var( $wpdb->prepare( 'SELECT `field_id` FROM `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_ids` WHERE `field_slug` = %s', $attribute_slug ) );
							if( ! empty( $attribute_id ) ) {
								if( is_array( $attribute_values ) ) {
									foreach( $attribute_values as $attribute_value ) {
										$attribute_value_id = $wpdb->get_var( $wpdb->prepare( 'SELECT `value_id` FROM `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_values` WHERE `field_id` = %d AND `value_name` = %s', $attribute_id, $attribute_value ) );
										if ( ! empty( $attribute_value_id ) ) {
											$query = $wpdb->prepare( 'INSERT IGNORE INTO `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_post_data`
												SET `field_id` = %d,
													`post_id` = %d,
													`post_value` = %s;',
											    $attribute_id,
                                                $post_id,
                                                $attribute_value_id
                                            );
											$wpdb->query( $query );
											$attribute_post_id = $wpdb->insert_id;
											$this->add_to_temp( 'attribute_posts', $post_type, $attribute_post_id, $attribute_post_id );
										}
									}
								} else {
									$query = $wpdb->prepare( 'INSERT IGNORE INTO `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_post_data`
										SET `field_id` = %d,
											`post_id` = %d,
											`post_value` = %s;',
									    $attribute_id,
                                        $post_id,
                                        $attribute_values
                                    );
									$wpdb->query( $query );
									$attribute_post_id = $wpdb->insert_id;
									$this->add_to_temp( 'attribute_posts', $post_type, $attribute_post_id, $attribute_post_id );
								}
							}
						}
					}

					if ( ! empty( $post_data['general'] ) ) {
						foreach( $post_data['general'] as $general_slug => $general_value ) {
							$general_id = $wpdb->get_var( $wpdb->prepare( 'SELECT `field_id` FROM `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_ids` WHERE `field_slug` = %s', $bws_bkng->plugin_prefix . '_' . $general_slug ) );
							if( ! empty( $general_id ) ) {
								$query = $wpdb->prepare( 'INSERT IGNORE INTO `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_post_data`
									SET `field_id` = %d,
										`post_id` = %d,
										`post_value` = %s;',
								    $general_id,
                                    $post_id,
                                    $general_value
                                );
								$wpdb->query( $query );
								$attribute_post_id = $wpdb->insert_id;
								$this->add_to_temp( 'attribute_posts', $post_type, $attribute_post_id, $attribute_post_id );
							}
						}
					}

					if ( ! empty( $post_data['post_meta'] ) ) {
                        $related_post_type = $post_data['post_meta']['products_connection']['related_post_type'];
						foreach ( $post_data['post_meta']['products_connection']['product_slug'] as $post_slug ) {
							$related_post_meta_id = $this->get_post( $post_slug );
                            $table = BWS_BKNG_DB_PREFIX . "{$post_data['post_type']}_{$related_post_type}_relations";
                            $wpdb->insert(
                                $table,
                                [
                                    "{$post_data['post_type']}_id" => $post_id,
                                    "{$related_post_type}_id" => $related_post_meta_id,
                                ]
                            );
						}
                        $post['related_post_type'] = $related_post_type;
                    }

                    $posts[] = $post;
				}
			}

            $this->add_to_temp( 'posts', 'data', $posts );
		}

		/**
		 * Add pages to settings
		 */
		if ( ! empty( $data['pages'] ) ) {
			$bike_options = $bws_bkng->get_post_type_option( 'bws_bike', '' );

			foreach( $data['pages'] as $page_settings_slug => $page_data_array  ) {
				if ( apply_filters( 'bws_bkng_prevent_demo_loading', false ) ) {
					continue;
				}
				$page = get_page_by_path( $page_data_array['slug'] );
				if( empty( $page ) ) {
					$args = array(
						'post_type'      => 'page',
						'comment_status' => 'closed',
						'ping_status'    => 'closed',
						'post_status'    => 'publish',
						'post_name'      => $page_data_array['slug'],
						'post_title'     => $page_data_array['title'],
						'post_content'   => '',
					);
					$page_id = absint( wp_insert_post( $args ) );
				} else {
					$page_id = $page->ID;
				}
				$bike_options[ $page_settings_slug ] = $page_id;
			}
			$bws_bkng->update_post_type_option( 'bws_bike', $bike_options );
		}
		/**
		 * Set demo options in order to remove previously installed data
		 */
		$options = array(
			'terms'             => empty( $this->temp['terms'] ) ? false : array_map( 'array_keys', array_map( 'array_flip', $this->temp['terms'] ) ),
			'posts'             => empty( $this->temp['posts'] ) ? false : $this->temp['posts']['data'],
			'pages'             => empty( $this->temp['pages'] ) ? false : array_keys( $this->temp['pages'] ),
			'attachments'       => empty( $this->temp['attachments'] ) ? false : array_keys( $this->temp['attachments'] ),
			'attributes'        => empty( $this->temp['attributes'] ) ? false : $this->temp['attributes'],
			'attribute_values'  => empty( $this->temp['attribute_values'] ) ? false : $this->temp['attribute_values'],
			'attribute_posts'   => empty( $this->temp['attribute_posts'] ) ? false : $this->temp['attribute_posts'],
			'value_locations'   => empty( $this->temp['value_locations'] ) ? false :  $this->temp['value_locations'] ,

		);
		update_option( $this->hook_slug, $options );
	}

	/**
	 * Inits the demo-data removing
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function remove() {
		global $bws_bkng, $wpdb;

		if ( ! empty( $this->option['terms'] ) ) {

			foreach( $this->option['terms'] as $taxonomy => $terms ) {

				if ( ! get_taxonomy( $taxonomy ) ) {
					continue;
				}
				$terms = array_filter( array_map( 'absint', $terms ) );

				foreach( $terms as $term_id ) {
					if( is_wp_error( wp_delete_term( $term_id, $taxonomy ) ) )
						$this->add_error( 'not_removed_term', array( 'taxonomy' => $taxonomy, 'term_id' => $term_id ) );
				}
			}
		}


		if ( ! empty( $this->option['posts'] ) ) {

			foreach( $this->option['posts'] as $post ) {
                if ( $post['related_post_type'] ) {
                    $table = BWS_BKNG_DB_PREFIX . "{$post['post_type']}_{$post['related_post_type']}_relations";
                    $wpdb->delete(
                        $table,
                        [
                            "{$post['post_type']}_id" => $post['ID']
                        ]
                    );
                }
				if ( ! wp_delete_post( $post['ID'], true ) ) {
					$this->add_error( 'not_removed_post', $post['ID'] );
				}
			}
		}

		if ( ! empty( $this->option['locations'] ) ) {
			foreach( $this->option['locations'] as $loc_id ) {
				if( ! wp_delete_post( $loc_id, true ) ) {
					$this->add_error( 'not_removed_post', $loc_id );
				}
			}
		}

		if ( ! empty( $this->option['attachments'] ) ) {
			foreach( $this->option['attachments'] as $attachment_id ) {
				if ( ! wp_delete_attachment( $attachment_id, true ) ) {
					$this->add_error( 'not_removed_attachment', $attachment_id );
				}
			}
		}

		if ( ! empty( $this->option['value_locations'] ) ) {
			foreach( $this->option['value_locations'] as $post_type => $loc ) {
				$wpdb->query( $wpdb->prepare(
                    'DELETE FROM `' . BWS_BKNG_DB_PREFIX .'locations`  
                        WHERE `location_id` = %d',
                        $loc
                    )
                );
			}
		}

		if ( ! empty( $this->option['attributes'] ) ) {
			foreach( $this->option['attributes'] as $post_type => $attributes ) {
				$wpdb->query( 'DELETE FROM `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_ids` WHERE `field_id` IN (' . implode( ',', $attributes ) . ');' );
			}
		}
		if ( ! empty( $this->option['attribute_values'] ) ) {
			foreach( $this->option['attribute_values'] as $post_type => $attribute_values ) {
				$wpdb->query( 'DELETE FROM `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_values` WHERE `value_id` IN (' . implode( ',', $attribute_values  ) . ');' );
			}
		}
		if ( ! empty( $this->option['attribute_posts'] ) ) {
			foreach( $this->option['attribute_posts'] as $post_type => $attribute_posts ) {
				$wpdb->query( 'DELETE FROM `' . BWS_BKNG_DB_PREFIX . $post_type . '_field_post_data` WHERE `id` IN (' . implode( ',', $attribute_posts ) . ');' );
			}
		}

		delete_option( $this->hook_slug );
	}

	/**
	 * The class constructor
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return void
	 */
	private function __construct() {
		global $bws_bkng;
		$this->hook_slug = 'bws_bkng_' . $bws_bkng->plugin_prefix . '_demo_data';
		$this->option    = get_option( $this->hook_slug );
	}

	/**
	 * Fetch the demo-data to be installed.
	 * The callback for this hook must be initialized in the Booking core wrapper
	 * @since  0.1
	 * @access private
	 * @param  void
	 * @return array     The list of demo-data
	 */
	private function get_data() {
		return apply_filters( $this->hook_slug, array() );
	}

    /**
     * Saves the term data to the DB
     * @param int|string $term_data The list of the term data
     * @param string $taxonomy The term taxonomy slug
     * @return void
     * @since  0.1
     * @access private
     */
	private function add_term( $term_data, $taxonomy ) {

		if ( empty( $term_data ) ) {
			return;
		}

		$result = term_exists( $term_data, $taxonomy );
		if ( is_array( $result ) ) {
			$this->add_to_temp( 'terms', $taxonomy, $result['term_id'], $term_data );
			return;
		}

		/* In case if you want to add some additional term data */
		$result = wp_insert_term( $term_data, $taxonomy );

		if ( is_wp_error( $result ) ) {
			$this->add_error( 'not_installed_term', array( 'taxonomy' => $taxonomy, 'term_slug' => $term_data ) );
			return;
		}

		$this->add_to_temp( 'terms', $taxonomy,  $result['term_id'], $term_data );

		$term_meta = empty( $term_data['term_meta'] ) ? false : array_filter( (array)$term_data['term_meta'] );

		if ( empty( $term_meta ) ) {
			return;
		}

		foreach( $term_meta as $key => $value ) {
			update_term_meta( $result['term_id'], 'bkng_' . $key, $value );
		}
	}

    /**
     * Saves the term data to the DB
     * @param string $term_slug The slug of the given term
     * @param string $taxonomy The term taxonomy slug
     * @return int|bool
     * @since  0.1
     * @access private
     */
	private function get_term_id( $term_slug, $taxonomy = BWS_BKNG_CATEGORIES ) {

		if ( ! empty( $this->temp['terms'][ $taxonomy ][ $term_slug ] ) )
			return $this->temp['terms'][ $taxonomy ][ $term_slug ];

		$term = get_term_by( 'slug', $term_slug, $taxonomy );

		if ( ! $term instanceof WP_Term ) {
			$this->add_error( 'not_existed_term', $term_slug );
			return false;
		}

		$this->add_to_temp( 'terms', $taxonomy, $term->term_id, $term->slug );

		return $term->term_id;
	}

	/**
	 * Fetch the product ID
	 * @uses   During binding extras to the product
	 * @since  0.1
	 * @access private
	 * @param  string $post_slug
	 * @return int
	 */
	private function get_post_id( $post_slug ) {

		if ( empty( $this->temp['posts'] ) )
			return $this->get_post( $post_slug );

		foreach( $this->temp['posts'] as $post_id => $data ) {
			if ( $data->post_name == $post_slug )
				return $post_id;
		}

		return false;
	}

	/**
	 * Fetch the product data
	 * @since  0.1
	 * @access private
	 * @param  string         $post_slug
	 * @return int|bool   An instance of WP_Post object if the product is founded, false otherwise
	 */
	private function get_post( $post_slug ) {
		global $bws_bkng;
		$args = array(
			'name'        => $post_slug,
			'post_type'   => $bws_bkng->get_post_types(),
			'post_status' => 'publish',
			'numberposts' => 1
		);
		$posts = get_posts( $args );

		if ( empty( $posts[0] ) )
			return false;

//		$this->add_to_temp( 'posts', $posts[0]->ID, $posts[0] );

		return $posts[0]->ID;
	}

	/**
	 * Add some necessary data for the further managing during the demo-data installation process
	 * @since  0.1
	 * @access private
	 * @param  string       $item      The item group ( 'post', 'attachment', etc. )
	 * @param  string       $key       The item slug
	 * @param  mixed        $value     The data to be saved to the temporary storage
	 * @param  string       $subkey    The sub-group key (used for storing the dependencies between products categories and attributes)
	 * @return void
	 */
	private function add_to_temp( $item, $key, $value, $subkey = '' ) {

		if ( empty( $this->temp[ $item ] ) )
			$this->temp[ $item ] = array();

		if ( empty( $subkey ) ) {
			$this->temp[ $item ][ $key ] = $value;
			return;
		}

		if ( empty( $this->temp[ $item ][ $key ] ) )
			$this->temp[ $item ][ $key ] = array();

		$this->temp[ $item ][ $key ][ $subkey ] = $value;
	}

	/**
	 * Set the feature image for the product
	 * @since  0.1
	 * @access private
	 * @param  string      $image       The image source url or path
	 * @param  int         $post_id     The product ID for which it is needed to set the featured image
	 * @return int|false
	 */
	private function set_thumbnail( $image, $post_id ) {

		$upload_dir = wp_upload_dir();

		if ( ! file_exists( $upload_dir['path'] ) && ! wp_mkdir_p( $upload_dir['path'] ) ) {
			$this->add_error( 'not_installed_attachment', $image );
			return false;
		}

		$allowed_types = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png'
		);
		$file_name = sanitize_file_name( basename( $image ) );
		$file_type = wp_check_filetype( $file_name, $allowed_types );

		/* if file name was wrong or the file isn't image */
		if ( empty( $file_name ) || 2 != count( array_filter( $file_type ) ) ) {
			$this->add_error( 'not_installed_attachment', $image );
			return false;
		}

		$is_url = preg_match( '/^http(s)?:\/\//', $image );

		if ( $is_url ) {
			$args = array(
				'timeout'     => 30,
				'httpversion' => '1.1'
			);
			$response = wp_remote_get( $image, $args );

			if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
				$this->add_error( 'not_installed_attachment', $image );
				return false;
			}

			$image_data = wp_remote_retrieve_body( $response );

			if ( empty( $image_data ) ) {
				$this->add_error( 'not_installed_attachment', $image );
				return false;
			}
		} elseif ( ! file_exists( $image ) ) {
			$this->add_error( 'not_installed_attachment', $image );
			return false;
		}

		$hash_name = hash_hmac('md5', $file_name, time() );
		$thumb     = "{$upload_dir['path']}/{$hash_name}.{$file_type['ext']}";
		$result    = $is_url ? file_put_contents( $thumb, $image_data ) : copy( $image, $thumb );

		if ( ! $result ) {
			$this->add_error( 'not_installed_attachment', $image );
			return false;
		}

		$args = array(
			'post_mime_type' => $file_type['type'],
			'post_title'     => $file_name,
			'post_content'   => '',
			'post_status'    => 'inherit'
		);

		$thumb_id = wp_insert_attachment( $args, $thumb, $post_id );

		if ( ! $thumb_id ) {
			$this->add_error( 'not_installed_attachment', $image );
			return false;
		}

		$this->add_to_temp( 'attachments', $thumb_id, $thumb_id );

		$thumb_data = wp_generate_attachment_metadata( $thumb_id, $thumb );
		wp_update_attachment_metadata( $thumb_id, $thumb_data );
		return set_post_thumbnail( $post_id, $thumb_id );
	}

	private function set_gallery_image( $image ) {

		$upload_dir = wp_upload_dir();

		if ( ! file_exists( $upload_dir['path'] ) && ! wp_mkdir_p( $upload_dir['path'] ) ) {
			$this->add_error( 'not_installed_attachment', $image );
			return false;
		}

		$allowed_types = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png'
		);
		$file_name = sanitize_file_name( basename( $image ) );
		$file_type = wp_check_filetype( $file_name, $allowed_types );

		/* if file name was wrong or the file isn't image */
		if ( empty( $file_name ) || 2 != count( array_filter( $file_type ) ) ) {
			$this->add_error( 'not_installed_attachment', $image );
			return false;
		}

		$is_url = preg_match( '/^http(s)?:\/\//', $image );

		if ( $is_url ) {
			$args = array(
				'timeout'     => 30,
				'httpversion' => '1.1'
			);
			$response = wp_remote_get( $image, $args );

			if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
				$this->add_error( 'not_installed_attachment', $image );
				return false;
			}

			$image_data = wp_remote_retrieve_body( $response );

			if ( empty( $image_data ) ) {
				$this->add_error( 'not_installed_attachment', $image );
				return false;
			}
		} elseif ( ! file_exists( $image ) ) {
			$this->add_error( 'not_installed_attachment', $image );
			return false;
		}

		$hash_name = hash_hmac('md5', $file_name, time() );
		$thumb     = "{$upload_dir['path']}/{$hash_name}.{$file_type['ext']}";
		$result    = $is_url ? file_put_contents( $thumb, $image_data ) : copy( $image, $thumb );

		if ( ! $result ) {
			$this->add_error( 'not_installed_attachment', $image );
			return false;
		}

		$args = array(
			'post_mime_type' => $file_type['type'],
			'post_title'     => $file_name,
			'post_content'   => '',
			'post_status'    => 'inherit'
		);

		$thumb_id = wp_insert_attachment( $args, $thumb );

		if ( ! $thumb_id ) {
			$this->add_error( 'not_installed_attachment', $image );
			return false;
		}

		$this->add_to_temp( 'attachments', $thumb_id, $thumb_id );

		$thumb_data = wp_generate_attachment_metadata( $thumb_id, $thumb );
		wp_update_attachment_metadata( $thumb_id, $thumb_data );
		return $thumb_id;
	}

	/**
	 * Adds error code to the array of codes.
	 * An ability to get the error message depending on the given error code
	 * have to be implemented in the get_errors() methods of child classes
	 * @since    0.1
	 * @access   public
	 * @param    string   $code    The error code
	 * @param    array    $data    The error data
	 * @return   void
	 */
	private function add_error( $code, $data = '' ) {
		if ( empty( $this->errors[ $code ] ) )
			$this->errors[ $code ] = array();

		$this->errors[ $code ][] = $data;
	}

	/**
	 * Fetch the error message accrording to the given error codes
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   object|false         An instance of the class WP_Error in case if some errors occurred, false otherwise
	 */
	public function get_errors() {

		if ( empty( $this->errors ) )
			return false;

		$errors = new WP_Error();

		foreach( $this->errors as $code => $data ) {
			switch( $code ) {
				case 'not_installed_term':
					$message = __( "Can't add terms", BWS_BKNG_TEXT_DOMAIN );
					break;
				case 'not_installed_post':
					$message = __( "Can't add posts", BWS_BKNG_TEXT_DOMAIN );
					break;
				case 'not_installed_attachment':
					$message = __( "Can't add thumbnail", BWS_BKNG_TEXT_DOMAIN );
					break;
				case 'not_existed_term':
					$message = __( "Terms don't exists", BWS_BKNG_TEXT_DOMAIN );
					break;
				case 'not_removed_term':
					$message = __( "Can't remove terms", BWS_BKNG_TEXT_DOMAIN );
					break;
				case 'not_removed_post':
					$message = __( "Can't remove posts", BWS_BKNG_TEXT_DOMAIN );
					break;
				case 'not_removed_attachment':
					$message = __( "Can't remove thumbnail", BWS_BKNG_TEXT_DOMAIN );
					break;
				default:
					$message = apply_filters( 'bws_bkng_demo_error', '', $code );
					break;
			}
			if ( ! empty( $message ) )
				$errors->add( $code, $message, $data );
		}
		return $errors;
	}

	/**
	 * Some magic to avoid the creation of several instances of this class
	 * @since  0.1
	 */
	private function __clone()  {}
	private function __sleep()  {}
	private function __wakeup() {}
}
