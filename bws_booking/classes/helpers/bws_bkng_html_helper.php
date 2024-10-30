<?php
/**
 * Contains methods are used for generation the HTML-structure
 * @since    Booking v0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

if ( class_exists( 'BWS_BKNG_HTML_Helper' ) )
	return;

class BWS_BKNG_HTML_Helper {

	/**
	 * Fetch the "no thumbnail" image file source.
	 * @since  0.1
	 * @access public
	 * @param  void
	 * @return string
	 */
	public function get_default_image_src() {
		return apply_filters( 'bws_bkng_default_image_src', plugins_url( 'images/no-image.png', dirname( dirname( __FILE__ ) ) ) );
	}

	/**
	 * Fetch the <img /> tag with "no thumbnail" image file source.
	 * @since  0.1
	 * @access public
	 * @param  $args       The list of the tag attributes.
	 * @return string
	 */
	public function get_default_image( $args ) {
		extract( $args );
		$title = empty( $title ) ? __( 'No image', BWS_BKNG_TEXT_DOMAIN ) : esc_attr( $title );
		$alt   = empty( $alt )   ? $title : esc_attr( $alt );
		$src   = empty( $src )   ? $this->get_default_image_src() : esc_attr( $src );
		$attr = empty( $attr )   ? '' : $this->parse_attr( $attr );
		return "<img src=\"{$src}\" title=\"{$title}\" alt=\"{$alt}\"{$attr}/>";
	}

	/**
	 * Fetch the <input type="checkbox"> form control.
	 * @since  0.1
	 * @access public
	 * @param  array $args  The list of the tag attributes and additional data
	 *                      to be displayed with the form control.
	 * @return string
	 */
	public function get_checkbox( $args ) {
		extract( $args );
		if( ! isset( $value ) || is_string( $value ) ) {
			$id     = empty( $id )     ? '' : esc_attr( $id );
			$id     = bws_bkng_sanitize_id( $id );
			$before = empty( $before ) ? '' : $before;
			$class  = empty( $class )  ? '' : esc_attr( $class );
			$name   = empty( $name )   ? '' : esc_attr( $name );
			$value  = isset( $value )  ? esc_attr( $value ) : 1;
			$attr   = empty( $attr )   ? '' : $this->parse_attr( $attr );
			$after  = empty( $after )  ? '' : $after;
			return
				"<label class=\"bws_bkng_label\" for=\"{$id}\">
					{$before}
					<input
					type=\"checkbox\"
					id=\"{$id}\"
					class=\"{$class}\"
					name=\"{$name}\"
					value=\"{$value}\"
					{$attr} />
					{$after}
				</label>";
		} else {
			$id     = empty( $id )     ? '' : esc_attr( $id );
			$id     = bws_bkng_sanitize_id( $id );
			$before = empty( $before ) ? '' : $before;
			$class  = empty( $class )  ? '' : esc_attr( $class );
            $name   = empty( $name )   ? '' : esc_attr( $name );
			$after  = empty( $after )  ? '' : $after;
			$checked = empty( $checked )  ? array() : $checked;
			$string = '<fieldset>';
			$count_element = count( $value );
			foreach( $value as $val ) {
				$attrib = empty( $attr )   ? '' : $this->parse_attr( $attr );
				$current_after  = empty( $after ) ? ( $count_element > 1 ? $val['value_name'] : '' ) : $after;
				foreach( $checked as $check_value ) {
					if( $check_value['post_value'] == $val['value_id'] ) {
						$attrib = ' checked="checked"';
					}
				}
				$string .=
					'<label class="bws_bkng_label" for="' . $id . '">
						' . $before . '
						<input
						type="checkbox"
						id="' . $id . '"
						class="' . $class . '"
						name="' . $name . '[]"
						value="' . $val['value_id'] . '"
						' . $attrib . ' />
						' . $current_after . '
					</label><br />';
			}
			$string .= '</fieldset>';
			return $string;
		}
	}

	/**
	 * Fetch the <input type="radiobox"> form control.
	 * @since  0.1
	 * @access public
	 * @param  array $args The list of the tag attributes and additional data
	 *                     to be displayed with the form control.
	 * @return string
	 */
	public function get_radiobox( $args ) {
		extract( $args );
		if( ! isset( $value ) || ! is_array( $value ) ) {
			$id     = empty( $id )     ? '' : esc_attr( $id );
			$id     = bws_bkng_sanitize_id( $id );
			$before = empty( $before ) ? '' : $before;
			$class  = empty( $class )  ? '' : esc_attr( $class );
			$name   = empty( $name )   ? '' : esc_attr( $name );
			$value  = isset( $value )  ? esc_attr( $value ) : '';
			$attr   = empty( $attr )   ? '' : $this->parse_attr( $attr );
			$after  = empty( $after )  ? '' : $after;
			return
				'<label class="bws_bkng_label" for="' . $id . '">
					' . $before . '
					<input
					type="radio"
					id="' . $id . '"
					class="' . $class . '"
					name="' . $name . '"
					value="' . $value . '"
					' . $attr . ' />
					' . $after . '
				</label>';
		} else {
			$id     = empty( $id )     ? '' : esc_attr( $id );
			$id     = bws_bkng_sanitize_id( $id );
			$before = empty( $before ) ? '' : $before;
			$class  = empty( $class )  ? '' : esc_attr( $class );
            $name   = empty( $name )   ? '' : esc_attr( $name );
			$after  = empty( $after )  ? '' : $after;
			$current = empty( $current ) ? '' : $current;
			$string = '<fieldset>';
			$count_element = count( $value );

			foreach ( $value as $val ) {
				$attrib = empty( $attr )   ? '' : $this->parse_attr( $attr );
				$current_after  = empty( $after ) ? ( $count_element > 1 ? $val['value_name'] : '' ) : $after;
				if ( $current == $val['value_id'] ) {
					$attrib = ' checked="checked"';
				}
				$string .=
					'<label class="bws_bkng_label" for="' . $id . '">
						' . $before . '
						<input
						type="radio"
						id="' . $id . '"
						class="' . $class . '"
						name="' . $name . '[]"
						value="' . $val['value_id'] . '"
						' . $attrib . ' />
						' . $current_after . '
					</label><br />';
			}

			$string .= '</fieldset>';
			return $string;
		}
	}

	/**
	 * Fetch the <input type="number"> form control.
	 * @since  0.1
	 * @access public
	 * @param  $args       The list of the tag attributes and additional data
	 *                     to be displayed with the form control.
	 * @return string
	 */
	public function get_number_input( $args ) {
		extract( $args );
		$id     = empty( $id )     ? ''          : esc_attr( $id );
		$id     = bws_bkng_sanitize_id( $id );
		$before = empty( $before ) ? ''          : $before;
		$class  = empty( $class )  ? ''          : esc_attr( $class );
		$name   = empty( $name )   ? ''          : esc_attr( $name );
		$value  = isset( $value )  ? esc_attr( $value ) : '';
		$min    = empty( $min )    ? 0           : esc_attr( $min );
		$max    = empty( $max )    ? PHP_INT_MAX : esc_attr( $max );
		$step   = empty( $step )   ? 1           : esc_attr( $step );
		$attr   = empty( $attr )   ? ''          : $this->parse_attr( $attr );
		$after  = empty( $after )  ? ''          : $after;
		return
			"<label class=\"bws_bkng_label\" for=\"{$id}\">
				{$before}
				<input
					type=\"number\"
					id=\"{$id}\"
					class=\"{$class}\"
					name=\"{$name}\"
					value=\"{$value}\"
					min=\"{$min}\"
					max=\"{$max}\"
					step=\"{$step}\"
					{$attr} />
				{$after}
			</label>";
	}

	/**
	 * Fetch the <input type="text"> form control.
	 * @since  0.1
	 * @access public
	 * @param  array $args  The list of the tag attributes and additional data
	 *                      to be displayed with the form control.
	 * @return string
	 */
	public function get_text_input( $args ) {
		extract( $args );
		$id        = empty( $id )        ? ''  : esc_attr( $id );
		$id        = bws_bkng_sanitize_id( $id );
		$before    = empty( $before )    ? ''  : $before;
		$class     = empty( $class )     ? ''  : esc_attr( $class );
		$name      = empty( $name )      ? ''  : esc_attr( $name );
		$value     = isset( $value )     ? esc_attr( $value ) : '';
		$maxlength = empty( $maxlength ) ? 255 : absint( $maxlength );
		$attr      = empty( $attr )      ? ''  : $this->parse_attr( $attr );
		$after     = empty( $after )     ? ''  : $after;
		return
			"<label class=\"bws_bkng_label\" for=\"{$id}\">
				{$before}
				<input
				type=\"text\"
				id=\"{$id}\"
				class=\"{$class}\"
				name=\"{$name}\"
				value=\"{$value}\"
				maxlength=\"{$maxlength}\"
				{$attr} />
				{$after}
			</label>";
	}

	/**
	 * Fetch the <input type="hidden"> form control.
	 * @since  0.1
	 * @access public
	 * @param  $args       The list of the tag attributes and additional data
	 *                     to be displayed with the form control.
	 * @return string
	 */
	public function get_hidden_input( $args ) {
		extract( $args );
		$id    = empty( $id )    ? '' : esc_attr( $id );
		$class = empty( $class ) ? '' : esc_attr( $class );
		$name  = empty( $name )  ? '' : esc_attr( $name );
		$value = isset( $value ) ? esc_attr( $value ) : '';;
		$attr  = empty( $attr )  ? '' : $this->parse_attr( $attr );
		return
			"<input
				type=\"hidden\"
				id=\"{$id}\"
				class=\"{$class}\"
				name=\"{$name}\"
				value=\"{$value}\"
				{$attr} />";
	}

	/**
	 * Fetch the <input type="submt|buttn|reset"> form control.
	 * @since  0.1
	 * @access public
	 * @param  $args       The list of the tag attributes and additional data
	 *                     to be displayed with the form control.
	 * @return string
	 */
	public function get_button_input( $args ) {
		extract( $args );
		$id        = empty( $id )     ? '' : esc_attr( $id );
		$before    = empty( $before ) ? '' : $before;
		$type      = empty( $type )   ? 'submit' : esc_attr( $type );
		$class     = empty( $class )  ? '' : esc_attr( $class );
		$name      = empty( $name )   ? '' : esc_attr( $name );
		$value     = isset( $value )  ? esc_attr( $value ) : '';
		$attr      = empty( $attr )   ? '' : $this->parse_attr( $attr );
		$after     = empty( $after )  ? '' : $after;
		$result = '';
		if ( $id ) {
			$result = "{$before}
						<input
						type=\"{$type}\"
						id=\"{$id}\"
						class=\"{$class}\"
						name=\"{$name}\"
						value=\"{$value}\"
						{$attr} />
						{$after}";
		} else {
			$result = "{$before}
						<input
						type=\"{$type}\"
						class=\"{$class}\"
						name=\"{$name}\"
						value=\"{$value}\"
						{$attr} />
						{$after}";
		}
		return
			$result;
	}

	/**
	 * Fetch the <input /> form control with the custom value of the "type" attribute (email, phone, etc.).
	 * @since  0.1
	 * @access public
	 * @param  $args       The list of the tag attributes and additional data
	 *                     to be displayed with the form control.
	 * @return string
	 */
	public function get_input( $args ) {
		extract( $args );
		$id        = empty( $id )     ? '' : esc_attr( $id );
		$id        = bws_bkng_sanitize_id( $id );
		$before    = empty( $before ) ? '' : $before;
		$type      = empty( $type )   ? '' : esc_attr( $type );
		$class     = empty( $class )  ? '' : esc_attr( $class );
		$name      = empty( $name )   ? '' : esc_attr( $name );
		$value     = isset( $value )  ? esc_attr( $value ) : '';
		$attr      = empty( $attr )   ? '' : $this->parse_attr( $attr );
		$after     = empty( $after )  ? '' : $after;
		return
			"<label class=\"bws_bkng_label\" for=\"{$id}\">
				{$before}
				<input
				type=\"{$type}\"
				id=\"{$id}\"
				class=\"{$class}\"
				name=\"{$name}\"
				value=\"{$value}\"
				{$attr} />
				{$after}
			</label>";
	}

	/**
	 * Fetch the <select> form control .
	 * @since  0.1
	 * @access public
	 * @param  $args       The list of the tag attributes and additional data
	 *                     to be displayed with the form control.
	 * @return string
	 */
	public function get_select( $args ) {
		extract( $args );
		$id       = empty( $id )    ? '' : esc_attr( $id );
		$id       = bws_bkng_sanitize_id( $id );
		$before   = empty( $before ) ? '' : $before;
		$class    = empty( $class ) ? '' : esc_attr( $class );
		$name     = empty( $name )  ? '' : esc_attr( $name );
		$attr     = empty( $attr )  ? '' : $this->parse_attr( $attr );
		$after    = empty( $after )  ? '' : $after;
//		$selected = empty( $selected ) ? ( 0 == $selected ? 0 : '' ) : esc_attr( $selected );
		$selected = empty( $selected ) ? '' : esc_attr( $selected );
        $options  = empty( $options ) || ! is_array( $options ) ? array() : $options;
		$content  = '';

		foreach ( $options as $value => $label ) {
            $opt_attr = $selected == $value ? ' selected="selected"' : '';
			$content .= "<option value=\"{$value}\"{$opt_attr}>{$label}</option>";
		}
		return
			"<label class=\"bws_bkng_label\" for=\"{$id}\">
				{$before}
				<select
				id=\"{$id}\"
				class=\"{$class}\"
				name=\"{$name}\"
				{$attr} >
					{$content}
				</select>
				{$after}
			</label>";
	}

	/**
	 * Fetch the additional form control such as <textarea></textarea>, <button></button>, etc.
	 * @since  0.1
	 * @access public
	 * @param  array $args    The list of the tag attributes and additional data
	 *                        to be displayed with the form control.
	 * @return string
	 */
	public function get_form_unit( $args ) {
		extract( $args );
		$unit      = empty( $unit )   ? 'textarea' :  esc_attr( $unit );
		$id        = empty( $id )     ? '' : esc_attr( $id );
		$before    = empty( $before ) ? '' : $before;
		$class     = empty( $class )  ? '' : esc_attr( $class );
		$name      = empty( $name )   ? '' : esc_attr( $name );
		$value     = isset( $value )  ? esc_attr( $value ) : '';
		$attr      = empty( $attr )   ? '' : $this->parse_attr( $attr );
		$after     = empty( $after )  ? '' : $after;
		return
			"{$before}
			<{$unit}
			id=\"{$id}\"
			class=\"{$class}\"
			name=\"{$name}\"
			{$attr} >{$value}</{$unit}>
			{$after}";
	}

	/**
	 * Fetch the HTML structure of the block to be displayed with the error message.
	 * @since  0.1
	 * @access public
	 * @param  string|object   $errors    The error message(s) to be displayed within the block.
	 * @param  string          $id        The block HTML 'id' attribute value.
	 * @param  string          $class     The block HTML 'class' attribute value.
	 * @return string
	 */
	public function get_errors( $errors, $id = '', $class = '' ) {
		global $bws_bkng;
		$id      = esc_attr( $id );
		$class   = esc_attr( $class );
		$content = '';
		if ( is_wp_error( $errors ) ) {
			$codes = $errors->get_error_codes();
			foreach ( (array)$codes as $code ) {
				$error    = $errors->get_error_message( $code );
				$data     = array_filter( (array)$errors->get_error_data( $code ) );
				$content .= "<p>{$error}</p>";
				if ( ! empty( $data ) ) {
					$error_details = preg_replace( "/\{\n([\s\S]*)\n\}/", '$1', json_encode( $bws_bkng->array_map_recursive( 'esc_html', (array)$data ), JSON_PRETTY_PRINT ) );
					$error_details = preg_replace( "/^[\s]{4}/", '', $error_details );
					$error_details = preg_replace( "/\n[\s]{4}/", "\n", $error_details );
					$content .= '<p><span class="bkng_show_error_details_button" title="' . __( 'More details', BWS_BKNG_TEXT_DOMAIN ) . '">...</span></p><pre class="bkng_error_details" style="display: none;">' . $error_details . '</pre>';
				}
			}
		} else if( '' != $errors ) {
			$error    = esc_html( strval( $errors ) );
			$content .= "<p>{$error}</p>";
		}

		return
			'<div id="' . $id . '" class="error notice bkng_error_wrap ' . $class . '">
				<div class="bkng_error_content">' .$content . '</div>
				<button type="button" class="notice-dismiss"></button>
			</div>';
	}

	/**
	 * Fetch the HTML structure of the block to be displayed with the custom message.
	 * @since  0.1
	 * @access public
	 * @param  string|array   $messages    The error message(s) to be displayed within the block.
	 * @param  string          $id         The block HTML 'id' attribute value.
	 * @param  string          $class      The block HTML 'class' attribute value.
	 * @return string
	 */
	public function get_messages( $messages, $id = '', $class = '' ) {
		$id      = esc_attr( $id );
		$class   = esc_attr( $class );
		$content = '';

		if ( is_array( $messages ) ) {
			$html = array();
			foreach ( $messages as $key => $message ) {
				$message = esc_html( strval( $message ) );
				$html[] = "<p>{$message}</p>";
			}
			$content .= implode( "", $html );
		} else {
			$messages = esc_html( strval( $messages ) );
			$content .= "<p>{$messages}</p>";
		}

		return
			"<div id=\"{$id}\" class=\"updated notice notice-success is-dismissible {$class}\">
				{$content}
				<button type=\"button\" class=\"notice-dismiss\"></button>
			</div>";
	}

	/**
	 * Fetch the HTML structure <a></a> tag.
	 * @since  0.1
	 * @access public
	 * @param  array     $args     The list of the tag attributes and additional data
	 *                                  to be displayed with the tag.
	 * @return string
	 */
	public function get_link( $args ) {
		extract( $args );
		$href   = empty( $href )   ? '#' : esc_attr( $href );
		$target = empty( $target ) ? '' : ' target="_blank"';
		$id     = empty( $id )     ? '' : esc_attr( $id );
		$class  = empty( $class )  ? '' : esc_attr( $class );
		$attr   = empty( $attr )   ? '' : $this->parse_attr( $attr );
		$text   = empty( $text )   ? '' : $text;
		return "<a id=\"{$id}\" class=\"{$class}\" href=\"{$href}\"{$target}{$attr}>{$text}</a>";
	}

	/**
	 * Santize the HTML tag attributes before adding them to tags.
	 * @since  0.1
	 * @access private
	 * @param  string   $attr     The raw list of attributes
	 * @return string
	 */
	private function parse_attr( $attr ) {
		return preg_replace_callback( "/((\S+)=(((\"){1}[^\"]*[\"]{1})|(('){1}[^']*[']{1})))|(\S+)/", array( $this, 'esc_attr' ), $attr );
	}

	/**
	 * Sanitize the HTML tag attributes values before adding them to tags.
	 * @see    self::parse_attr()
	 * @since  0.1
	 * @access private
	 * @param  array   $matches
	 * @return string
	 */
	private function esc_attr( $matches ) {
		/* cleanup non-values attributes ( e.g. required ) */
		if ( ! empty( $matches[8] ) ) {
			return $matches[8];
		/* cleanup attributes such as checked='checked' */
		} elseif ( ! empty( $matches[7] ) ) {
			return $matches[2]. "='" . esc_attr( trim( $matches[3], $matches[7] ) ) . "'";
		/* cleanup attributes such as checked="checked" */
		} elseif ( ! empty( $matches[5] ) ) {
			return $matches[2]. '="' . esc_attr( trim( $matches[3], $matches[5] ) ) . '"';
		} else {
			return '';
		}
	}
}
