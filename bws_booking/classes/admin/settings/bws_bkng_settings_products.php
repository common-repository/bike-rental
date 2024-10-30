<?php
/**
 * Handle the content of "Products" tab
 * @since  Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */

if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Settings_Products' ) )
	return;

class BWS_BKNG_Settings_Products extends BWS_BKNG_Settings_Tabs {

	/**
	 * Class constructor
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function __construct() {
		$this->tabs = array(
			'general' => array( 'label' => __( 'General', BWS_BKNG_TEXT_DOMAIN ) )
		);

		parent::__construct();
	}

	/**
	 * Prepares the plugin options before further saving to database
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function prepare_options( $options = NULL ) {
		global $bws_bkng, $bws_post_type;

		$sort_products_by = $bws_bkng->get_order_by_fields( true );
		$sort_products    = array( 'asc', 'desc' );

		foreach( $bws_post_type as $post_key => $post_type ) {
			if ( NULL !== $options ) {
				$options[ $post_key ] = array();
			}

			/* booleans */
			$this->options['enable_variations']            = ! empty( $_POST['bkng_enable_variations'][ $post_key ] );
			$this->options['enable_likes']                 = ! empty( $_POST['bkng_enable_likes'][ $post_key ] );
			$this->options['allow_likes_for_unauthorized'] = ! empty( $_POST['bkng_allow_likes_for_unauthorized'][ $post_key ] );
			$this->options['search_by_statuses']           = ! empty( $_POST['bkng_search_by_statuses'][ $post_key ] );

			/* numbers */
			$this->options['products_page'] = absint( $_POST['bkng_products_page'][ $post_key ] );

			/* variations */
			$this->options['sort_products_by']      = in_array( $_POST['bkng_sort_products_by'][ $post_key ], $sort_products_by ) ? sanitize_text_field( stripslashes( $_POST['bkng_sort_products_by'][ $post_key ] ) ) : $this->options['sort_products_by'][ $post_key ];
			$this->options['sort_products']         = isset( $_POST['bkng_sort_products'] ) && in_array( $_POST['bkng_sort_products'][ $post_key ], $sort_products ) ? sanitize_text_field( stripslashes( $_POST['bkng_sort_products'][ $post_key ] ) ) : $this->options['sort_products'][ $post_key ];

			/* prepare list of products statuses before saving */
			$defaults      = array( 'available', 'not_available', 'reserved', 'in_use' );
			$statuses      = array_map( 'sanitize_text_field', $_POST['bkng_products_statuses'][ $post_key ] );
			$all_statuses  = array_keys( $statuses );
			$user_statuses = array_diff( $all_statuses, $defaults );
			foreach ( $defaults as $default ) {
				$this->options['products_statuses'][ $post_key ][ $default ] = array(
					'title'   => isset( $statuses[ $default ]['title'] ) ? sanitize_text_field( esc_html( $statuses[ $default ]['title'] ) ) : '',
					'default' => true
				);
			}

			/* if there are more statuses */
			if ( ! empty( $user_statuses ) ) {
				foreach( $user_statuses as $old_slug ) {
					$new_slug = sanitize_title( $statuses[ $old_slug ]['slug'] );
					if ( $old_slug != $new_slug )
						unset( $this->options[ $old_slug ] );
					$this->options['products_statuses'][ $post_key ][ $new_slug ] = array(
						'title' => trim( esc_html( $statuses[ $old_slug ]['title'] ) )
					);
				}
			}

			/* if user filled in "new status" fields */
			if ( ! empty( $_POST['bkng_products_new_status'][ $post_key ]['slug'] ) ) {
				$new_slug = sanitize_title( $_POST['bkng_products_new_status'][ $post_key ]['slug'] );
				if ( ! empty( $new_slug ) && ! array_key_exists( $new_slug, array_keys( $this->options['products_statuses'][ $post_key ] ) ) ) {
					if ( NULL !== $options ) {
						$this->options['products_statuses'][ $post_key ][ $new_slug ] = array(
							'title' => sanitize_text_field( stripslashes( $_POST['bkng_products_new_status'][ $post_key ]['title'] ) )
						);
					} else {
						$this->options['products_statuses'][ $new_slug ] = array(
							'title' => sanitize_text_field( stripslashes( $_POST['bkng_products_new_status'][ $post_key ]['title'] ) )
						);
					}
				}
			}

			if ( NULL !== $options ) {
				$options[ $post_key ]['enable_variations'] 				= $this->options['enable_variations'];
				$options[ $post_key ]['enable_likes']               	= $this->options['enable_likes'];
				$options[ $post_key ]['allow_likes_for_unauthorized']   = $this->options['allow_likes_for_unauthorized'];
				$options[ $post_key ]['search_by_statuses']             = $this->options['search_by_statuses'];
				$options[ $post_key ]['products_page'] 					= $this->options['products_page'];
				$options[ $post_key ]['sort_products_by']     			= $this->options['sort_products_by'];
				$options[ $post_key ]['sort_products']         			= $this->options['sort_products'];
				$options[ $post_key ]['products_statuses'] = $this->options['products_statuses'][ $post_key ];
			}
		}

		if ( NULL !== $options ) {
			return $options;
		}
	}

	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function tab_general() {
		global $bws_bkng, $bws_post_type;
		$product_label = sanitize_text_field( stripslashes( $bws_post_type[ $_GET['post_type'] ]['labels']['name'] ) ); ?>
		<table class="form-table">
			<?php if ( $bws_bkng->allow_variations ) {
				/**
				 * Enable Variations checkbox
				 */
				$name = 'bkng_enable_variations';
				$attr = $this->options['enable_variations'] ? ' checked="checked"' : '';

				$content = $bws_bkng->get_checkbox( compact( 'name', 'attr' ) );

				$bws_bkng->display_table_row( __( 'Enable Variations', BWS_BKNG_TEXT_DOMAIN ), $content );
			}

			/**
			 * Products statuses options
			 */
			$name  = 'bkng_search_by_statuses';
			$after = __( 'Add to the search filters', BWS_BKNG_TEXT_DOMAIN );
			$attr  = $this->options['search_by_statuses'] ? ' checked="checked"' : '';

			$content = $bws_bkng->get_checkbox( compact( 'name', 'attr', 'after' ) );

			$bws_bkng->display_table_row( $product_label . ' ' . __( 'Statuses', BWS_BKNG_TEXT_DOMAIN ), $content );


			$slug_title  = __( 'Slug', BWS_BKNG_TEXT_DOMAIN );
			$label_title = __( 'Label', BWS_BKNG_TEXT_DOMAIN );

			$arg   = $this->options['products_statuses'];
			$table = "<div id=\"bkng_status_endpoints_list\"><p><span>{$slug_title}</span><span>{$label_title}</span></p>%s</div>";
			$table_rows = '';
			$link_href  = admin_url( 'admin.php?page=' . $bws_bkng->settings_page_slug . '%s' );
			foreach( $arg as $slug => $data ) {
				$is_default = ! empty( $data['default'] );
				$name       = "bkng_products_statuses[{$slug}][slug]";
				$value      = $slug;
				$maxlength  = 64;
				$before     = "<span class=\"bkng_input_status_input_label\">{$slug_title}</span>";
				$attr       = $is_default ? 'readonly="readonly"' : '';
				$slug_input = $bws_bkng->get_text_input( compact( 'name', 'value', 'maxlength', 'attr', 'before' ) );

				$name        = "bkng_products_statuses[{$slug}][title]";
				$value       = $data['title'];
				$before      = "<span class=\"bkng_input_status_input_label\">{$label_title}</span>";
				$label_input = $bws_bkng->get_text_input( compact( 'name', 'value', 'before' ) );

				$remove_link = '';
				if ( ! $is_default ) {
					$id    = "bkng_remove_status_{$slug}";
					$href  = sprintf( $link_href, "&bkng_confirm=remove_status&status={$slug}" );
					$class = "bkng_action_link bkng_action_link_remove dashicons dashicons-dismiss ";
					$text  = '';
					$attr  = ' title="' . __( 'Remove Status', BWS_BKNG_TEXT_DOMAIN ) . '"';
					$remove_link = $bws_bkng->get_link( compact( 'href', 'text', 'id', 'class', 'attr' ) );
				}

				$table_rows .= "<p>{$slug_input}{$label_input}{$remove_link}</p>";
			}

			$name      = 'bkng_products_new_status[slug]';
			$maxlength = 64;
			$attr      = 'placeholder="' . __( 'Enter the new status slug', BWS_BKNG_TEXT_DOMAIN ) . '"';
			$new_slug_input = $bws_bkng->get_text_input( compact( 'name', 'maxlength', 'attr' ) );

			$name = 'bkng_products_new_status[title]';
			$attr      = 'placeholder="' . __( 'Enter the new status label', BWS_BKNG_TEXT_DOMAIN ) . '"';
			$new_title_input = $bws_bkng->get_text_input( compact( 'name', 'attr' ) );

			$unit   = 'button';
			$name   = 'bkng_add_status';
			$class  = "bkng_action_link bkng_action_link_add dashicons dashicons-plus";
			$attr  = ' title="' . __( 'Save Status', BWS_BKNG_TEXT_DOMAIN ) . '"';
			$button = $bws_bkng->get_form_unit( compact( 'unit', 'class', 'name', 'attr' ) );

			$table_rows .= "<p id=\"bkng_add_status_row\">{$new_slug_input}{$new_title_input}{$button}</p>";

			$content = sprintf( $table, $table_rows );

			$bws_bkng->display_table_row( '', $content );

			/**
			 * Products page select
			 */
			$content = $bws_bkng->get_list_pages( 'products_page' );

			$bws_bkng->display_table_row( $product_label . ' ' . __( 'Page', BWS_BKNG_TEXT_DOMAIN ), $content );

			/**
			 * Sort options
			 */
			$name     = 'bkng_sort_products_by';
			$options  = $bws_bkng->get_order_by_fields();
			$selected = $this->options['sort_products_by'];

			$content = $bws_bkng->get_select( compact( 'name', 'options', 'selected' ) );

			$bws_bkng->display_table_row( sprintf( __( 'Sort %s by', BWS_BKNG_TEXT_DOMAIN ), $product_label . ' ' ) , $content );

			$sort_options = array(
				'asc'  => __( 'Ascending (e.g., 1, 2, 3; a, b, c)', BWS_BKNG_TEXT_DOMAIN ),
				'desc' => __( 'Descending (e.g., 3, 2, 1; c, b, a)', BWS_BKNG_TEXT_DOMAIN )
			);
			$name    = 'bkng_sort_products';
			$content = '';
			foreach( $sort_options as $value => $after ) {
				$attr = $value == $this->options['sort_products'] ? ' checked="checked"': '';
				$content .= '<p>' . $bws_bkng->get_radiobox( compact( 'name', 'attr', 'value', 'after' ) ) . '</p>';
			}
			$bws_bkng->display_table_row( sprintf( __( 'Sort %s', BWS_BKNG_TEXT_DOMAIN ), $product_label . ' ' ), $content );

			/**
			 * Options are temporary unavailable
			 * @todo finalize in v1.0
			 * $name  = 'bkng_enable_likes';
			 * $attr  = $this->options["enable_likes"] ? 'checked="checked"' : '';
			 * $after = __( 'Enable', BWS_BKNG_TEXT_DOMAIN );
			 * $enable_likes = $bws_bkng->get_checkbox( compact( 'name', 'attr', 'after' ) );

			 * $name = 'bkng_allow_likes_for_unauthorized';
			 * $attr = $this->options["allow_likes_for_unauthorized"] ? 'checked="checked"' : '';
			 * $after = __( 'Allow likes for unauthorized users', BWS_BKNG_TEXT_DOMAIN );
			 * $allow_for_unauthorized = $bws_bkng->get_checkbox( compact( 'name', 'attr', 'after' ) );

			 * $content = "<p>{$enable_likes}</p><p>{$allow_for_unauthorized}</p>";

			 * $bws_bkng->display_table_row( __( 'Products Likes', BWS_BKNG_TEXT_DOMAIN ), $content );
			*/ ?>
		</table>
	<?php }

	/**
	 * Displays the tab content
	 * @since    0.1
	 * @access   public
	 * @param    void
	 * @return   void
	 */
	public function display_tab_content( $post_type = NULL ) {
		global $bws_bkng, $bws_post_type;

        if ( NULL !== $post_type ) {
			$_GET['post_type'] = $post_type;
		}

		$product_label = sanitize_text_field( stripslashes( $bws_post_type[ $_GET['post_type'] ]['labels']['name'] ) ); ?>
		<table class="form-table">
			<?php if ( $bws_bkng->allow_variations ) {
				/**
				 * Enable Variations checkbox
				 */
				$name = "bkng_enable_variations[{$post_type}]";
				$attr = $this->options[ $post_type ]['enable_variations'] ? ' checked="checked"' : '';

				$content = $bws_bkng->get_checkbox( compact( 'name', 'attr' ) );

				$bws_bkng->display_table_row( __( 'Enable Variations', BWS_BKNG_TEXT_DOMAIN ), $content );
			}

			/**
			 * Products statuses options
			 */
			$name  = "bkng_search_by_statuses[{$post_type}]";
			$after = __( 'Add to the search filters', BWS_BKNG_TEXT_DOMAIN );
			$attr  = $this->options[ $post_type ]['search_by_statuses'] ? ' checked="checked"' : '';

			$content = $bws_bkng->get_checkbox( compact( 'name', 'attr', 'after' ) );

			$bws_bkng->display_table_row( $product_label . ' ' . __( 'Statuses', BWS_BKNG_TEXT_DOMAIN ), $content );


			$slug_title  = __( 'Slug', BWS_BKNG_TEXT_DOMAIN );
			$label_title = __( 'Label', BWS_BKNG_TEXT_DOMAIN );

			$arg   = $this->options[ $post_type ]['products_statuses'];

			$table = "<div id=\"bkng_status_endpoints_list\"><p><span>{$slug_title}</span><span>{$label_title}</span></p>%s</div>";
			$table_rows = '';
			$link_href  = admin_url( "edit.php?page={$bws_bkng->settings_page_slug}%s" );
			foreach( $arg as $slug => $data ) {
				$is_default = ! empty( $data['default'] );
				$name       = "bkng_products_statuses[{$post_type}][{$slug}][slug]";
				$value      = $slug;
				$maxlength  = 64;
				$before     = "<span class=\"bkng_input_status_input_label\">{$slug_title}</span>";
				$attr       = $is_default ? 'readonly="readonly"' : '';
				$slug_input = $bws_bkng->get_text_input( compact( 'name', 'value', 'maxlength', 'attr', 'before' ) );

				$name        = "bkng_products_statuses[{$post_type}][{$slug}][title]";
				$value       = $data['title'];
				$before      = "<span class=\"bkng_input_status_input_label\">{$label_title}</span>";
				$label_input = $bws_bkng->get_text_input( compact( 'name', 'value', 'before' ) );

				$remove_link = '';
				if ( ! $is_default ) {
					$id    = "bkng_remove_status_{$slug}";
					$href  = sprintf( $link_href, "&post_type={$post_type}&bkng_confirm=remove_status&status={$slug}&action={$post_type}" );
					$class = "bkng_action_link bkng_action_link_remove dashicons dashicons-dismiss ";
					$text  = '';
					$attr  = ' title="' . __( 'Remove Status', BWS_BKNG_TEXT_DOMAIN ) . '"';
					$remove_link = $bws_bkng->get_link( compact( 'href', 'text', 'id', 'class', 'attr' ) );
				}

				$table_rows .= "<p>{$slug_input}{$label_input}{$remove_link}</p>";
			}


			$name      = "bkng_products_new_status[{$post_type}][slug]";
			$maxlength = 64;
			$attr      = 'placeholder="' . __( 'Enter the new status slug', BWS_BKNG_TEXT_DOMAIN ) . '"';
			$new_slug_input = $bws_bkng->get_text_input( compact( 'name', 'maxlength', 'attr' ) );

			$name = "bkng_products_new_status[{$post_type}][title]";
			$attr      = 'placeholder="' . __( 'Enter the new status label', BWS_BKNG_TEXT_DOMAIN ) . '"';
			$new_title_input = $bws_bkng->get_text_input( compact( 'name', 'attr' ) );

			$unit   = 'button';
			$name   = "bkng_add_status[{$post_type}]";
			$class  = "bkng_action_link bkng_action_link_add dashicons dashicons-plus";
			$attr  = ' title="' . __( 'Save Status', BWS_BKNG_TEXT_DOMAIN ) . '"';
			$button = $bws_bkng->get_form_unit( compact( 'unit', 'class', 'name', 'attr' ) );

			$table_rows .= "<p id=\"bkng_add_status_row\">{$new_slug_input}{$new_title_input}{$button}</p>";

			$content = sprintf( $table, $table_rows );

			$bws_bkng->display_table_row( '', $content );

			/**
			 * Products page select
			 */
			$content = $bws_bkng->get_list_pages( 'products_page', $post_type );

			$bws_bkng->display_table_row( $product_label . ' ' . __( 'Page', BWS_BKNG_TEXT_DOMAIN ), $content );

			/**
			 * Sort options
			 */
			$name     = "bkng_sort_products_by[{$post_type}]";
			$options  = $bws_bkng->get_order_by_fields();
			$selected = $this->options[$post_type]['sort_products_by'];

			$content = $bws_bkng->get_select( compact( 'name', 'options', 'selected' ) );

			$bws_bkng->display_table_row( sprintf( __( 'Sort %s by', BWS_BKNG_TEXT_DOMAIN ), $product_label . ' ' ) , $content );

			$sort_options = array(
				'asc'  => __( 'Ascending (e.g., 1, 2, 3; a, b, c)', BWS_BKNG_TEXT_DOMAIN ),
				'desc' => __( 'Descending (e.g., 3, 2, 1; c, b, a)', BWS_BKNG_TEXT_DOMAIN )
			);
			$name    = "bkng_sort_products[{$post_type}]";
			$content = '';
			foreach( $sort_options as $value => $after ) {
				$attr = $value == $this->options[ $post_type ]['sort_products'] ? ' checked="checked"': '';
				$content .= '<p>' . $bws_bkng->get_radiobox( compact( 'name', 'attr', 'value', 'after' ) ) . '</p>';
			}
			$bws_bkng->display_table_row( sprintf( __( 'Sort %s', BWS_BKNG_TEXT_DOMAIN ), $product_label . ' ' ), $content );

			/**
			 * Options are temporary unavailable
			 * @todo finalize in v1.0
			 * $name  = 'bkng_enable_likes';
			 * $attr  = $this->options["enable_likes"] ? 'checked="checked"' : '';
			 * $after = __( 'Enable', BWS_BKNG_TEXT_DOMAIN );
			 * $enable_likes = $bws_bkng->get_checkbox( compact( 'name', 'attr', 'after' ) );

			 * $name = 'bkng_allow_likes_for_unauthorized';
			 * $attr = $this->options["allow_likes_for_unauthorized"] ? 'checked="checked"' : '';
			 * $after = __( 'Allow likes for unauthorized users', BWS_BKNG_TEXT_DOMAIN );
			 * $allow_for_unauthorized = $bws_bkng->get_checkbox( compact( 'name', 'attr', 'after' ) );

			 * $content = "<p>{$enable_likes}</p><p>{$allow_for_unauthorized}</p>";

			 * $bws_bkng->display_table_row( __( 'Products Likes', BWS_BKNG_TEXT_DOMAIN ), $content );
			*/ ?>
		</table>
	<?php }

}
