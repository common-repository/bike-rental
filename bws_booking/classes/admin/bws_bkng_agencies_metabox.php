<?php
/**
 * @uses     To handle the additional data for Agencies
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Agencies_Metabox' ) )
	return;

class BWS_BKNG_Agencies_Metabox extends BWS_BKNG_Term_Metabox {

	/**
	 * The list of meta-fields that activated on the Agencies settings page
	 * @since  0.1
	 * @access private
	 * @var    array
	 */
	private $enabled_meta;

	/**
	 * Whether the current page is the add-new-term page or edit-existed-term page
	 * @uses   Due to the fact taht add-new-term and edit-existed-term pages are build using different layout types
	 * @since  0.1
	 * @access private
	 * @var    boolen
	 */
	private $is_edit_page;

	/**
	 * The image Gallery - an instance of the class BWS_BKNG_Image_Gallery
	 * @since  0.1
	 * @access private
	 * @var    object
	 */
	private $gallery;

	/**
	 * The Agencies taxonomy data - an instance of the class WP_Taxonomy
	 * @since  0.1
	 * @access private
	 * @var    object
	 */
	private $taxonomy;

	/**
	 * Class constructor
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function __construct() {
		global $bws_bkng;

		parent::__construct( BWS_BKNG_AGENCIES, __CLASS__ );

		new BWS_BKNG_Default_Term( BWS_BKNG_AGENCIES );

		if ( ! $this->get_terms() )
			$this->add_default_term();
		/**
		 * Redirect during transition from old version of plugin to current version
		 * @deprecated 1.0.8
		 * @todo Remove function after 01.06.2019
		 */
		if ( isset( $_GET['flag'] ) && 'true' == $_GET['flag'] ) {
			return;
		}
		/**
		 * End deprecated
		 */
		$this->enabled_meta = array_filter( $bws_bkng->get_option( "agencies_additional_meta" ), array( $this, 'remove_disabled' ) );

		$this->taxonomy = get_taxonomy( BWS_BKNG_AGENCIES );

		/* Clone term */
		if ( isset( $_GET['action'] ) && 'clone' == $_GET['action'] ) {
			$term_id = absint( $_REQUEST['term_id'] );
			check_admin_referer( "clone-{$term_id}" );
			$this->clone_term( $term_id );
		}

		/* adds the "Clone" link to the list of agencies */
		add_filter( BWS_BKNG_AGENCIES . "_row_actions", array( $this, 'add_clone_link' ), 10, 2);
		/* adds service messages */
		add_filter( "term_updated_messages", array( $this, "add_messages" ) );
	}


	/**
	 * Adds the "Clone" link to the list of agencies
	 * @since  0.1
	 * @access public
	 * @param  array      $actions       Row actions
	 * @param  object     $tag           An object of WP_Term class
	 * @return array      $actions
	 * @return void
	 */
	public function add_clone_link( $actions, $tag ) {

		if ( ! current_user_can( $this->taxonomy->cap->edit_terms ) )
			return $actions;

		$actions['clone'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			wp_nonce_url( "edit-tags.php?action=clone&amp;taxonomy={$this->tax_slug}&amp;term_id={$tag->term_id}", "clone-{$tag->term_id}" ),
			esc_attr( __( 'Clone', BWS_BKNG_TEXT_DOMAIN ) ),
			__( 'Clone', BWS_BKNG_TEXT_DOMAIN )
		);

		return $actions;
	}

	/**
	 * Clones the agency
	 * @since  0.1
	 * @access public
	 * @param  int     $term_id   The current agency ID
	 * @return void
	 */
	public function clone_term( $term_id ) {
		global $wpdb;

		if ( ! current_user_can( $this->taxonomy->cap->edit_terms ) )
			return;

		$cloned_term = get_term( $term_id, BWS_BKNG_AGENCIES );

		if ( ! $cloned_term || is_wp_error( $cloned_term ) )
			return;

		/*
		 * Get the new agency slug
		 */

		/*
		 * removes "-copy" and "-copy-{number}" suffix from the term slug (if it was previously cloned)
		 * in order to get original slug and avoid duplicate the "-copy" suffix in the new term slug.
		 */
		$clone_slug = preg_replace( "/^(.*)?[-]{1}copy[-]{0,1}[\d]{0,}$/i", '$1', $cloned_term->slug );

		/* get the new term unique suffix */
		$suffix = $this->get_clone_slug_suffix( $clone_slug );

		/*
		 * Get the new agency name
		 */
		$copy_string = __( "Copy", BWS_BKNG_TEXT_DOMAIN );
		$reg_exp     = "/{$copy_string}[\s]{0,1}[\d]*/i";

		/* if the cloned agency has never been cloned before */
		if ( empty( $suffix ) ) {
			$clone_name = "{$cloned_term->name} {$copy_string}";

		/* if the cloned agency is the clone of another agency */
		} elseif ( preg_match( $reg_exp, $cloned_term->name ) ) {
			$clone_name = preg_replace( $reg_exp, "{$copy_string} {$suffix}", $cloned_term->name );

		/* if the agency will be cloned the first time */
		} else {
			$clone_name = "{$cloned_term->name} {$copy_string} {$suffix}";
		}

		/*
		 * Save the new agency to database
		 */
		$params = array(
			'description' => $cloned_term->description,
			'slug'        => "{$clone_slug}-copy" . ( empty( $suffix ) ? '' : "-{$suffix}" ),
			'parent'      =>  $cloned_term->parent
		);
		$clone = wp_insert_term( $clone_name, BWS_BKNG_AGENCIES, $params );

		/* Make redirect in order to avoid dublicate of action by refreshing the page */
		$location = remove_query_arg(
			array( '_wp_http_referer', '_wpnonce', 'action', 'term_id' ),
			wp_unslash( $_SERVER['REQUEST_URI'] )
		);

		if ( ! empty( $_REQUEST['paged'] ) )
			$location = add_query_arg( 'paged', absint( $_REQUEST['paged'] ), $location );

		if ( empty( $clone ) || is_wp_error( $clone) ) {
			$location = add_query_arg( 'message', 2, $location );
		} else {
			$wpdb->query(
			    $wpdb->prepare(
                    "INSERT INTO `{$wpdb->termmeta}`
                    (`term_id`, `meta_key`, `meta_value`)
                    ( SELECT {$clone['term_id']} AS `term_id`, `meta_key`, `meta_value`
                      FROM `{$wpdb->termmeta}`
                      WHERE `term_id`= %d
                    );",
                    $cloned_term->term_id
                )
			);

			$location = empty( $wpdb->last_error ) ? add_query_arg( 'message', 3, $location ) : add_query_arg( 'message', 4, $location );
		}

		wp_redirect( apply_filters( 'redirect_term_location', $location, $this->taxonomy ) );
		exit;
	}

	/**
	 * Fetch the unique suffix for the new agency during the cloning
	 * @since  0.1
	 * @access public
	 * @param  string     $term_slug   The original term slug
	 * @return false|int               False, if the agency will be the first time cloned, Integer otherwise
	 */
	public function get_clone_slug_suffix( $term_slug ) {
		global $wpdb;

		if ( ! term_exists( "{$term_slug}-copy", BWS_BKNG_AGENCIES ) )
			return false;

		$offset = strlen( $term_slug ) + 7;
        $like = '%' . $wpdb->esc_like( $term_slug ) . '-copy%';
		$result = $wpdb->get_col(
		    $wpdb->prepare(
		        "SELECT SUBSTRING(`slug`,{$offset}) FROM `{$wpdb->terms}` WHERE `slug` LIKE %s",
                $like
            )
        );

		$max = absint( max( (array)$result ) );

		return empty( $max ) ? 2 : $max + 1;
	}

	/**
	 * Add service message in order to notice the user about action results
	 * @since  0.1
	 * @see    wp-admin/includes/edit-tag-messages.php
	 * @access public
	 * @param  array      $messages      The list of registered service messages
	 * @return array      $messages
	 */
	public function add_messages( $messages ) {


		if ( empty( $messages[ BWS_BKNG_AGENCIES ] ) )
			$messages[ BWS_BKNG_AGENCIES ] = array();

		/* the 0th and 1st items are set in BWS_BKNG_Default_Term:add_messages() */
		$messages[ BWS_BKNG_AGENCIES ][2] = __( 'Term is not cloned', BWS_BKNG_TEXT_DOMAIN );
		$messages[ BWS_BKNG_AGENCIES ][3] = __( 'Term is cloned', BWS_BKNG_TEXT_DOMAIN );
		$messages[ BWS_BKNG_AGENCIES ][4] = __( 'Term is cloned with some errors. Check term options', BWS_BKNG_TEXT_DOMAIN );

		return $messages;
	}

	/**
	 * Checks whether the value is not empty
	 * @see    self::__construct()
	 * @since  0.1
	 * @access public
	 * @param  $item
	 * @return boolean
	 */
	public function remove_disabled( $item ) {
		return !! $item;
	}

	/**
	 * Fetch the agencies list
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return array|false       The list of term taxonomies data. False otherwise.
	 */
	public function get_terms() {
		global $bws_bkng;
		$terms = $bws_bkng->get_terms( BWS_BKNG_AGENCIES, array( 'hide_empty' => false ) );
		return is_wp_error( $terms ) || empty( $terms ) ? false : $terms;
	}

	/**
	 * Creates the default agency
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function add_default_term() {
		global $bws_bkng;
		$result = wp_insert_term( 'Agency', BWS_BKNG_AGENCIES );

		if ( is_wp_error( $result ) )
			return;

		/**
		 * add location data
		 */
		$data = array(
			'address'   => $bws_bkng->get_option( 'google_map_default_address' ),
			'latitude'  => $bws_bkng->get_option( 'google_map_default_lat' ),
			'longitude' => $bws_bkng->get_option( 'google_map_default_lng' )
		);

		foreach( $data as $key => $value )
			update_term_meta( $result['term_id'], "bkng_term_{$key}", $value );
	}

	/**
	 * Fetch an HTML-structure of metaboxes
	 * @see BWS_BKNG_Term_Metabox
	 * @since  0.1
	 * @access public
	 * @param  object    $tag,
	 * @param  string    $tax_slug,
	 * @param  boolean   $is_edit_page
	 * @return string    HML-structure of content
	 */
	public function get_items( $tag, $tax_slug = null, $is_edit_page ) {
		global $bws_bkng;
		$this->is_edit_page = $is_edit_page;
		$id = $this->is_edit_page ? $tag->term_id : 0;
		$this->gallery = array_intersect( array_keys( $this->enabled_meta ), array( 'featured_image', 'image_gallery' ) ) ? new BWS_BKNG_Image_Gallery( $id, "bkng_agency_gallery", false ) : false;
		$meta_fields = $bws_bkng->get_agencies_meta_fields();
		$items = array();
		foreach ( $this->enabled_meta as $field => $value ) {
			switch ( $field ) {
				case 'location':
					$content = BWS_BKNG_Locations_Metabox::get_location_metabox( $tag );
					break;
				case 'featured_image':
				case 'image_gallery':
					$single  = 'featured_image' == $field;
					$content = $this->gallery->get_content( $single );
					if ( ! has_action( 'admin_footer', array( 'BWS_BKNG_Image_Gallery', 'enque_scripts' ) ) )
						add_action( 'admin_footer', array( 'BWS_BKNG_Image_Gallery', 'enque_scripts' ) );
					break;
				case 'phone':
					$func    = "get_{$field}_field";
					$content = $this->$func( $tag );
					break;
				case 'working_hours':
					$content = $this->get_working_hours_field( $tag );
					add_action( 'admin_footer', array( $bws_bkng, 'add_datepicker_scripts' ) );
					break;
				default:
					$content = apply_filters( 'bws_bkng_meta_field', '', $field );
					break;
			}

			if ( empty( $content ) )
				continue;

			$items[ $field ] = array(
				'label'   => empty( $meta_fields[ $field ] ) ? $field : $meta_fields[ $field ],
				'content' => $content
			);
		}

		return $items;
	}

	/**
	 * Save the agency meta data
	 * @see BWS_BKNG_Term_Metabox
	 * @since  0.1
	 * @access public
	 * @param  int      $term_id,
	 * @param  int      $tt_id,
	 * @param  string   $tax_slug
	 * @return void
	 */
	public function save_term_data( $term_id, $tt_id, $tax_slug = '' ) {
		global $bws_bkng;

		/* don't need to do anything if we need to clone the agency or there is no additional metaboxes to display */
		if ( ( ! empty( $_REQUEST['action'] ) && 'clone' == $_REQUEST['action'] ) || empty( $this->enabled_meta ) )
			return;

		foreach ( $this->enabled_meta as $field => $value ) {
			switch ( $field ) {
				case 'location':
					$location = new BWS_BKNG_Locations_Metabox( BWS_BKNG_AGENCIES, __CLASS__ );
					$location->save_term_data( $term_id, $tt_id, $tax_slug );
					break;
				case 'featured_image':
				case 'image_gallery':
					$gallery = new BWS_BKNG_Image_Gallery( $term_id, "bkng_agency_gallery", false );
					$gallery->save_images();
					break;
				case 'phone':
					$field = "bkng_agency_phone";
					$phone = empty( $_POST[ $field ] ) ? '' : esc_attr( trim( $_POST[ $field ] ) );
					if ( $bws_bkng->is_valid_phone( $phone ) )
						update_term_meta( $term_id, $field, $phone );
					break;
				case 'working_hours':
					$field = 'bkng_agency_working_hours';
					if ( empty( $_POST[ $field ] ) || ! is_array( $_POST[ $field ] ) )
						update_term_meta( $term_id, $field, '' );

					$schedule  = array();
					$defaults = array(
						'work_from' => date( get_option( 'time_format' ), 3600 * 9 ),
						'work_till' => date( get_option( 'time_format' ), 3600 * 17 )
					);
					for( $i = 0; $i < 7; $i ++ ) {
						$schedule[ $i ]['holiday'] = ! empty( sanitize_text_field( $_POST[ $field ][ $i ]['holiday'] ) );
						foreach( $defaults as $key => $default ) {
							$$key = empty( $_POST[ $field ][ $i ][ $key ] ) ? $default : trim( $_POST[ $field ][ $i ][ $key ] );
							$schedule[ $i ][ $key ] = esc_sql( $bws_bkng->is_valid_time( $$key ) ? $$key : $default );
						}
					}
					$schedule['notes'] = empty( $_POST[ $field ]['notes'] ) ? '' : sanitize_text_field( stripslashes( $_POST[ $field ]['notes']  ) );
					update_term_meta( $term_id, $field, serialize( $schedule ) );
					break;
				default:
					do_action( 'bws_bkng_term_save_meta', compact( 'field', 'term_id', 'tt_id', 'tax_slug' ) );
					break;
			}
		}
	}

	/**
	 * Fetch the HTML-structure of Phone metabox
	 * @since  0.1
	 * @access public
	 * @param  object   $tag
	 * @return string
	 */
	public function get_phone_field( $tag ) {
		global $bws_bkng;
		$name  = "bkng_agency_phone";
		$id    = $name;
		$value = $this->is_edit_page ? get_term_meta( $tag->term_id, $name, true ) : '';
		return $bws_bkng->get_text_input( compact( 'name', 'id', 'value' ) );
	}

	/**
	 * Fetch the HTML-structure of Shedule metabox
	 * @since  0.1
	 * @access public
	 * @param  object   $tag
	 * @return string
	 */
	public function get_working_hours_field( $tag ) {
		global $bws_bkng;

		$array_name  = "bkng_agency_working_hours";
		$time_format = get_option( 'time_format' );
		$week_days   = $bws_bkng->get_week_days( true );
		try {
			if ( $this->is_edit_page ) {
				$data    = get_term_meta( $tag->term_id, $array_name, true );
				$shedule = maybe_unserialize( $data );
			} else {
				$shedule = array();
			}
		} catch( Excerption $e ) {
			$shedule = array();
		}

		$table =
			'<table class="bkng_meta_working_hours">
				<thead>
					<tr>
						<th scope="row" rowspan="2"></th>
						<td rowspan="2">' . __( 'Holiday', BWS_BKNG_TEXT_DOMAIN ) . '?</td>
						<td colspan="2">' . __( 'Workday', BWS_BKNG_TEXT_DOMAIN ) . '</td>
					</tr>
					<tr>
						<td>' . __( 'from', BWS_BKNG_TEXT_DOMAIN ) . '</td>
						<td>' . __( 'till', BWS_BKNG_TEXT_DOMAIN ) . '</td>
					</tr>
				</thead>
				<tbody>%1$s<tbody>
				<tfoot>
					<tr>
						<th>'  . __( 'Notes', BWS_BKNG_TEXT_DOMAIN ) . '</th>
						<td colspan="3">%2$s</td>
					</tr>
				</tfoot>
			</table>';
		$row =
			'<tr>
				<th>%1$s</th>
				<td>%2$s</td>
				<td>%3$s</td>
				<td>%4$s</td>
			</tr>';
		$rows = '';

		/**
		 * The list of week days is displayed according to the site settings
		 * @see Admin Panel->Settings->General->Week Starts On
		 */
		$key        = get_option( 'start_of_week' );
		$open_hour  = date( get_option( 'time_format' ), 3600 * 9 );
		$close_hour = date( get_option( 'time_format' ), 3600 * 17 );

		for ( $i = 0; $i < 7; $i++ ) {
			$data = empty( $shedule[ $key ] ) ? array() : $shedule[ $key ];

			$name = "{$array_name}[$key][holiday]";
			$attr = empty( $data['holiday'] ) ? '' : 'checked="checked"';
			$holiday_checkbox = $bws_bkng->get_checkbox( compact( 'name', 'attr' ) );

			$name  = "{$array_name}[$key][work_from]";
			$value = empty( $data['work_from'] ) ? $open_hour : esc_attr( $data['work_from'] );
			$id    = "bkng_work_from_{$key}";
			$class = 'bkng_timepicker bkng_work_from';
			$work_from = $bws_bkng->get_text_input( compact( 'name', 'value', 'class', 'id' ) );

			$name  = "{$array_name}[$key][work_till]";
			$value = empty( $data['work_till'] ) ? $close_hour : esc_attr( $data['work_till'] );
			$id    = "bkng_work_till_{$key}";
			$class = 'bkng_timepicker bkng_work_till';
			$work_till = $bws_bkng->get_text_input( compact( 'name', 'value', 'class', 'id' ) );

			$rows .= sprintf( $row, ucfirst( $week_days[$key] ), $holiday_checkbox, $work_from, $work_till );

			$key = 6 <= $key ? 0 : $key + 1;
		}

		$id    = "bws_bkng_working_hours_notice";
		$name  = "{$array_name}[notes]";
		$value = empty( $shedule['notes'] ) ? '' : esc_attr( $shedule['notes'] );
		$notes = $bws_bkng->get_text_input( compact( 'name', 'value', 'id' ) );
		return sprintf( $table, $rows, $notes );
	}
}
