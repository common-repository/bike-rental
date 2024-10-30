<?php
/**
 * Helper for settings page
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_Settings_Helper' ) )
	return;

class BWS_BKNG_Settings_Helper {

	/**
	 * Displays the form open tag
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return void
	 */
	public function form_open() { ?>
		<form id="bkng_settings_form" class="bws_form" method="post" action="">
	<?php }

	/**
	 * Displays the form close tag and
	 * @since  0.1
	 * @access public
	 * @param  boolean $show_buttons  Wheteher to show submit button
	 * @return void
	 */
	public function form_close( $show_buttons = true ) {
		global $bws_bkng;
		$plugin_info = $bws_bkng->get_plugin_info();
			if ( $show_buttons ) { ?>
				<p class="submit">
					<input id="bws-submit-button" name="bkng_settings_submit" type="submit" class="button button-primary" value="<?php _e( 'Save Changes', BWS_BKNG_TEXT_DOMAIN ); ?>" />
				</p>
			<?php }
			wp_nonce_field( BWS_BKNG_PATH, 'bkng_nonce_name' ); ?>
		</form>
		<?php
		if ( ! $show_buttons )
			return;
		bws_form_restore_default_settings( BWS_BKNG_PATH );
		bws_plugin_reviews_block( $plugin_info['Name'], 'booking' );
	}

	/**
	 * Displays the list of vertical tabs
	 * @since  0.1
	 * @access public
	 * @param  array      $tabs           The list of tabs Labels
	 * @param  string     $wrap_tag       The HTML tag of list items wrap
	 * @param  string     $item_tag       The HTML tag of list item
	 * @return void
	 */
	public function display_tabs( $tabs, $wrap_tag = 'ul', $item_tag = 'li' ) {
		$wrap_tag = sanitize_title( $wrap_tag );
		$item_tag = sanitize_title( $item_tag );
		$content = '';
		foreach ( $tabs as $slug => $label ) {
			$slug  = sanitize_title( $slug );
			$label = esc_html( $label );
			$content .= "<{$item_tag}>
					<a class=\"bkng_tab_link_{$slug}\" href=\"#bkng_tab_{$slug}\">{$label}</a>
				</{$item_tag}>";
		}
		printf( '<div id="bkng_tab_links_background"></div><%1$s id="bkng_preferences_tab_links">%2$s</%1$s>', $wrap_tag, $content );
	}

	/**
	 * Displays the option content inside <tr> HTML tag
	 * @since  0.1
	 * @access public
	 * @param  array      $label       The row label
	 * @param  string     $content     The row HTML content
	 * @param  string     $class       The row class
	 * @return void
	 */
	public function display_table_row( $label = '', $content = '', $class = '' ) { ?>
		<tr class="<?php echo esc_attr( $class ); ?>">
			<th scope="row"><?php echo $label; ?></th>
			<td><?php echo $content; ?></td>
		</tr>
	<?php }

	/**
	 * Fetch the list of created pages as dropdown menu
	 * ( the HTML tag <select> )
	 * @since  0.1
	 * @access public
	 * @param  string      $name         Value of the attribute "name" without plugin prefix
	 * @return string
	 */
	public function get_list_pages( $name, $post_type = NULL ) {
		global $bws_bkng;
		$name = esc_attr( $name );
        $selected = '';
		if( NULL !== $post_type ) {
			$option = $bws_bkng->get_option( $post_type  );
			if( isset( $option[ $name ] ) ) {
				$selected = $option[ $name ];
			}
		}
		return wp_dropdown_pages( array(
			'name'              => ( ! is_null( $post_type ) ? "bkng_{$name}[{$post_type}]" : "bkng_{$name}" ),
			'echo'              => 0,
			'option_none_value' => '0',
			'selected'          => ( NULL !== $post_type  ? $selected : $bws_bkng->get_option( $name ) ),
		));
	}

	/**
	 * Displays the mail editor
	 * @since  0.1
	 * @access public
	 * @param  string      $name         Value of the attribute "name" without prefix
	 * @param  boolean     $add_media    Whether to add media buttons to editor
	 * @param  string      $content      The editor content
	 * @return string
	 */
	public function get_notice_editor( $name, $add_media = false, $content = null ) {
		global $wpdb;
		$post_type = sanitize_text_field( stripslashes( $_GET['post_type'] ) );
		$table = BWS_BKNG_DB_PREFIX . $post_type . '_notifications';
		$name  = esc_attr( $name );
		$settings  = array(
			'wpautop'       => true,
			'media_buttons' => absint( $add_media ),
			'textarea_name' => "bkng_notice[{$name}][body]",
			'textarea_rows' => 4,
			'tabindex'      => null,
			'editor_css'    => '',
			'editor_class'  => "bkng_{$name}",
			'teeny'         => false,
			'dfw'           => false,
			'tinymce'       => false,
			'quicktags'     => true
		);
		if ( ! is_string( $content ) )
			$content = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT `body` FROM `{$table}` WHERE `type`=%s LIMIT 1;",
                    $name
                )
            );
		ob_start();
		wp_editor( $content, "bkng_{$name}", $settings );
		$editor = ob_get_contents();
		ob_end_clean();
		return $editor;
	}

	/**
	 * Fetch the list of currencies
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return array    The list of currencies
	 */
	public function get_currencies() {
		global $bws_bkng;
		$currencies = $bws_bkng->data_loader->load( 'currencies' );
		return array_map( array( $this, 'implode_for_select' ), $currencies );
	}

	/**
	 * Fetch the list of date format
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return array    The list of currencies
	 */
	public function get_date_format() {
		global $bws_bkng;
		$date_format = $bws_bkng->data_loader->load( 'date_format' );
		return array_map( array( $this, 'implode_for_select' ), $date_format );
	}

	/**
	 * Forms the currency title into format "currnecy_name (currency_symbol)"
	 * @since  0.1
	 * @access public
	 * @param  array
	 * @return string
	 */
	public function implode_for_select( $item ) {
		if( is_array( $item ) ) {
			return $item[0] . '&nbsp;(' . $item[1] . ')';
		} else {
			return $item;
		}
	}

	/**
	 * Fetch the HTML structure of the block to be displayed as tooltip.
	 * @since  0.1
	 * @access public
	 * @param  string|array   $messages    The error message(s) to be displayed within the block.
	 * @param  string          $id         The block HTML 'id' attribute value.
	 * @param  string          $class      The block HTML 'class' attribute value.
	 * @param  string          $icon       The icon  HTML 'class' attribute value.
	 * @return string
	 */
	public function get_tooltip( $content, $id = '', $class = '', $icon = '' ) {
		if ( ! wp_style_is( 'dashicons' ) )
			wp_enqueue_style( 'dashicons' );
		$id    = esc_attr( $id );
		$class = esc_attr( $class );
		$icon  = esc_attr( $icon );
		if ( empty( $icon ) )
			$icon = 'editor-help';
		return
			"<span id=\"{$id}\" class=\"bws_help_box dashicons dashicons-{$icon} {$class}\">
					<span class=\"bws_hidden_help_text\">{$content}</span>
			</span>";
	}

	public function get_info( $content ) {
		return "<span class=\"bws_info\">{$content}</span>";
	}
}
