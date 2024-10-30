<?php /**
 * Contains the list of functions are used to handle search form and toolbar
 * @since    0.1
 * @package  Booking
 * @author   BestWebSoft
 */
if ( ! defined( 'ABSPATH' ) )
	die();

/**
 * Displays a drop-down list with links in a post sorter filter
 * @since    0.1
 * @param    array    $params       a list for filtering
 * @param    string   $current      currently selected value
 * @param    string   $query_var    request parameter name
 * @return   void
 */
if ( ! function_exists( 'bws_bkng_link_list' ) ) {
	function bws_bkng_link_list( $params, $current, $query_var ) {
		$list =
			'<ul class="bws_bkng_item_list %1$s">
				<li class="bws_bkng_selected_item">
					<span class="bws_bkng_selected_item_text">%2$s</span>
					<ul>%3$s</ul>
				</li>
			</ul>';
		$a        = '<a href="%1$s">%2$s</a>';
		$item     = "<li>{$a}</li>";
		$selected = $items = '';

		foreach ( (array)$params as $value => $label ) {
			$link = bws_bkng_get_link( $value, $query_var );
			if ( $value == $current )
				$selected = sprintf( '<span class="bws_bkng_toolbar_selected">%s</span>', $label );
			else
				$items .= sprintf( $item, $link, $label );
		}

		echo sprintf( $list, $query_var, $selected, $items );
	}
}

/**
 * Generates a link for a product sorting filter, adding the necessary parameters
 * @since    0.1
 * @param    string    $value        Parameter value
 * @param    string    $query_var    Parameter name
 * @return   string                  Link
 */
if ( ! function_exists( 'bws_bkng_get_link' ) ) {
	function bws_bkng_get_link( $value, $query_var ) {
		global $bws_bkng;

		$current_url = rtrim( $bws_bkng->get_current_url(), '/' );

		/* remove the currently set parameter value */
		$current_url = add_query_arg( $query_var, false, $current_url );
		return add_query_arg( $query_var, $value, $current_url );
	}
}

/**
 * Displays links in the filter to change the appearance of product display
 * @since    0.1
 * @param    void
 * @return   void
 */
if ( ! function_exists( 'bws_bkng_views_list' ) ) {
	function bws_bkng_views_list() {
		$views = array(
			'grid' => __( 'Grid view', BWS_BKNG_TEXT_DOMAIN ),
			'list' => __( 'List view', BWS_BKNG_TEXT_DOMAIN )
		);
		$query = bws_bkng_get_query();
		foreach ( $views as $view => $label ) {
			if (
				( array_key_exists( 'view', $query ) && $view == $query['view'] ) ||
				( ! array_key_exists( 'view', $query ) && 'grid' == $view )
			) { ?>
				<span class="dashicons dashicons-<?php echo esc_attr( $view ) ?>-view" title="<?php echo esc_attr( $label ); ?>"></span>
			<?php } else { ?>
				<a class="dashicons dashicons-<?php echo esc_attr( $view ) ?>-view" href="<?php echo esc_url( bws_bkng_get_link( $view, 'view' ) ); ?>" title="<?php echo esc_attr( $label ); ?>"></a>
			<?php }
		}
	}
}

/**
 * Fetch the list of parameters by which you can order products
 * @since    0.1
 * @param    void
 * @return   array
 */
if ( ! function_exists( 'bws_bkng_get_orders_fields' ) ) {
	function bws_bkng_get_orders_fields() {
		global $bws_bkng;
		return $bws_bkng->get_order_by_fields();
	}
}

/**
 * Gets an array of data to form the content of the search filter
 * @since    0.1
 * @param    void
 * @return   array|false
 */
if ( ! function_exists( 'bws_bkng_get_search_fields' ) ) {
	function bws_bkng_get_search_fields() {
		$filter = BWS_BKNG_Search_Filter::get_instance();
		$fields = $filter->get_fields();
		return empty( $fields ) ? false : array_filter( array_map( 'array_filter', $fields ) );
	}
}

/**
 * Gets an array of data to form the content of the search filter
 * @since    0.1
 * @param    void
 * @return   array|false
 */
if ( ! function_exists( 'bws_bkng_get_search_form_fields' ) ) {
	function bws_bkng_get_search_form_fields( $bws_post_type ) {
		global $bws_bkng, $bws_search_form_parameters;
		if ( isset( $bws_search_form_parameters[ $bws_post_type ] ) && ! empty( $bws_search_form_parameters[ $bws_post_type ] ) ) {
			foreach ( $bws_search_form_parameters[ $bws_post_type ] as $field_key => $field ) {
				switch ( $field_key ) {
					case 'location':
						if ( 'text' == $field['format'] ) { ?>
							<div class="bws_bkng_search_products_item bws_bkng_search_products_location">
								<?php $name = 'bws_bkng_' . $field_key;
								$class = '';
								$value = isset( $_GET['bws_bkng_location'] ) ? sanitize_text_field( stripslashes( $_GET['bws_bkng_location'] ) ) : '';
								$attr  = '' != $field['placeholder'] ? 'placeholder="' . $field['placeholder'] . '"': '';
								$bws_field = $bws_bkng->get_text_input( compact( 'class', 'name', 'value', 'attr' ) );
								if ( ! empty( $field['label'] ) ) { ?>
									<p class="bws_bkng_filter_label"><?php echo esc_html( $field['label'] ); ?></p>
								<?php }
								echo $bws_field; ?>
							</div><!-- .bws_bkng_search_products_item bws_bkng_search_products_location -->
						<?php }
						break;
					case 'from':
						if ( 'data' == $field['format'] ) { ?>
							<div class="bws_bkng_search_products_item bws_bkng_search_products_datepicker">
								<div class="bws_bkng_filter_datetimepicker">
									<?php if ( ! empty( $field['label'] ) ) { ?>
										<p class="bws_bkng_filter_label"><?php echo esc_html( $field['label'] ); ?></p>
									<?php }
									$name = 'bws_bkng_' . $field_key;
									$class = 'bws_bkng_datepicker';
									$value = isset( $_GET['bws_bkng_from'] ) ? sanitize_text_field( stripslashes( $_GET['bws_bkng_from'] ) ) : '';
									/* To prevent keyboard showing on mobile devices */
									$attr  = 'onfocus="blur();" data-display-time="hide"';
									$bws_field = $bws_bkng->get_text_input( compact( 'class', 'name', 'value', 'attr' ) );
									echo $bws_field; ?>
								</div><!-- .bws_bkng_filter_datetimepicker -->
							</div><!-- .bws_bkng_search_products_item.bws_bkng_search_products_datepicker -->
						<?php } else { ?>
							<div class="bws_bkng_search_products_item bws_bkng_search_products_datepicker">
								<?php bws_bkng_datetimepicker( 1, array( 'label' => $field['label'], 'value' => '' ), 'bws_bkng_from' ); ?>
							</div><!-- .bws_bkng_search_products_item.bws_bkng_search_products_datepicker -->
						<?php }
						break;
					case 'till':
						if ( 'data' == $field['format'] ) { ?>
							<div class="bws_bkng_search_products_item bws_bkng_search_products_datepicker">
								<div class="bws_bkng_filter_datetimepicker">
									<?php if ( ! empty( $field['label'] ) ) { ?>
										<p class="bws_bkng_filter_label"><?php echo esc_html( $field['label'] ); ?></p>
									<?php }
									$name = 'bws_bkng_' . $field_key;
									$class = 'bws_bkng_datepicker';
									$value = isset( $_GET['bws_bkng_till'] ) ? sanitize_text_field( stripslashes( $_GET['bws_bkng_till'] ) ) : '';
									/* To prevent keyboard showing on mobile devices */
									$attr  = 'onfocus="blur();" data-display-time="hide"';
									$bws_field = $bws_bkng->get_text_input( compact( 'class', 'name', 'value', 'attr' ) );
									echo $bws_field; ?>
								</div><!-- .bws_bkng_filter_datetimepicker -->
							</div><!-- .bws_bkng_search_products_item.bws_bkng_search_products_datepicker -->
						<?php } else { ?>
							<div class="bws_bkng_search_products_item bws_bkng_search_products_datepicker">
								<?php bws_bkng_datetimepicker( 2, array( 'label' => $field['label'], 'value' => '' ), 'bws_bkng_till' ); ?>
							</div><!-- .bws_bkng_search_products_item.bws_bkng_search_products_datepicker -->
						<?php }
						break;
					case 'adult': ?>
						<div class="bws_bkng_search_products_item bws_bkng_search_products_adult">
							<?php $name = 'bws_bkng_' . $field_key;
							$class = '';
							$attr  = '';
							$before = $field['label'];
							$options = array( '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8 );
							$selected = isset( $_GET['bws_bkng_adult'] ) ? sanitize_text_field( stripslashes( $_GET['bws_bkng_adult'] ) ) : '1';
							$bws_field = $bws_bkng->get_select( compact( 'class', 'name', 'selected', 'attr', 'before', 'options' ) );
							echo $bws_field; ?>
						</div><!-- .bws_bkng_filter_datetimepicker.bws_bkng_search_products_adult -->
						<?php break;
					case 'kid': ?>
						<div class="bws_bkng_search_products_item bws_bkng_search_products_kid">
							<?php $name = 'bws_bkng_' . $field_key;
							$class = '';
							$selected = isset( $_GET['bws_bkng_kid'] ) ? sanitize_text_field( stripslashes( $_GET['bws_bkng_kid'] ) ): '0';
							$attr  = '';
							$options = array( '0' => '0', '1' => 1, '2' => 2, '3' => 3, '4' => 4 );
							$before = $field['label'];
							$bws_field = $bws_bkng->get_select( compact( 'class', 'name', 'selected', 'attr', 'options', 'before' ) );
							echo $bws_field; ?>
						</div><!-- .bws_bkng_filter_datetimepicker.bws_bkng_search_products_kid -->
						<?php break;
				}
			}
		}
	}
}

if ( ! function_exists( 'bws_bkng_field_label' ) ) {
	function bws_bkng_field_label( $field, $label, $label_position ) {
		if ( ! empty( $label ) ) {
			switch ( $label_position ) {
				case 'none':
					return $field;
					break;
				case 'wrap_left':
					return '<label>' . $label . ' ' . $field . '</label>';
					break;
				case 'wrap_right':
					return '<label>' . $field . ' ' . $label . '</label>';
					break;
				case 'left':
					return '<label>' . $label . '</label> ' . $field;
					break;
				case 'right':
					return $field . ' <label>' . $label . '</label>';
					break;
			}
		} else {
			return $field;
		}
	}
}

if ( ! function_exists( 'bws_bkng_hidden_inputs' ) ) {
	function bws_bkng_hidden_inputs() {
		global $bws_bkng;

		foreach ( bws_bkng_get_session_rent_interval() as $key => $value ) {
			$name = 'bws_bkng_' . $key;
			echo $bws_bkng->get_hidden_input( compact( 'name', 'value' ) );
		}

		$is_one_category = $bws_bkng->is_only_one_category();
		if ( $is_one_category ) {
			$name = BWS_BKNG_CATEGORIES;
			$value = $is_one_category->slug;
			echo $bws_bkng->get_hidden_input( compact( 'name', 'value' ) );
		}
	}
}


if ( ! function_exists( 'bws_bkng_datetimepicker' ) ) {
    /**
     * Generates the HTML structure of the datepicker
     * @since    0.1
     * @param    string   $key     Names for datetimepicker's fields
     * @param    array    $data    Additional attrs for datetimepicker structure
     * @param    string   $name    Inputs' names
     * @param    boolean  $return  Whether to return or display the picker's content
     * @return   void|string
     */
	function bws_bkng_datetimepicker( $key, $data, $name = '', $return = false ) {
		global $bws_bkng;
		if ( $return ) {
			ob_start();
		} ?>

		<div class="bws_bkng_filter_datetimepicker bws_bkng_filter_datetimepicker_<?php echo esc_attr( $key ); ?>">
			<?php if ( ! empty( $data['label'] ) ) { ?>
				<p class="bws_bkng_filter_label"><?php echo esc_html( $data['label'] ); ?></p>
			<?php }
			$name = empty( $name ) ? $key : sprintf( $name, $key );
			$class = "bws_bkng_datepicker";
			$value = empty( $data['value'] ) ? '' : $data['value'];
			/* To prevent keyboard showing on mobile devices */
			$attr  = 'data-display-time="hide" onfocus="blur();" required="required"';
			echo $bws_bkng->get_text_input( compact( 'class', 'name', 'value', 'attr' ) ); ?>
		</div><!-- .bws_bkng_filter_datetimepicker.bws_bkng_filter_datetimepicker_<?php echo $key; ?> -->

		<?php if ( $return ) {
			$content = ob_get_clean();
			return $content;
		}
	}
}

if ( ! function_exists( 'bws_bkng_get_datetimepicker_data' ) ) {
	function bws_bkng_get_datetimepicker_data( $from = '', $till = '' ) {
		if ( empty( $from ) || empty( $till ) )
			extract( bws_bkng_get_session_rent_interval() );
		$args = array(
			'from' => array( 'label' => __( 'Pick-up Date', BWS_BKNG_TEXT_DOMAIN ), 'value' => $from ),
			'till' => array( 'label' => __( 'Drop-off Date and Time', BWS_BKNG_TEXT_DOMAIN ), 'value' => $till )
		);
		return apply_filters( 'bws_bkng_datepicker_data', $args );
	}
}

if ( ! function_exists( 'bws_bkng_datetimepicker_form' ) ) {
    /**
     * @param string $name
     * @param string $from
     * @param string $till
     * @param false $return
     * @return string|void
     */
	function bws_bkng_datetimepicker_form( $name = 'bws_bkng_%s', $from = '', $till = '', $return = false ) {
		$html = '';
		$date_array = bws_bkng_get_datetimepicker_data( $from, $till );

		foreach ( $date_array as $key => $data ) {
			$html .= bws_bkng_datetimepicker( $key, $data, $name, true );
		}
		if ( $return ) {
			return $html;
		}
		echo $html;
	}
}

if ( ! function_exists( 'bws_bkng_datetimepicker_data' ) ) {
    /**
     * @param string $from
     * @param string $till
     * @param false $return
     * @return string|void
     */
	function bws_bkng_datetimepicker_data( $from = '', $till = '', $return = false ) {
		global $bws_bkng;
		$date_dormat = $bws_bkng->get_option( 'date_format' ). ' ' . get_option( 'time_format' );
		$html = '';

		$date_array = bws_bkng_get_datetimepicker_data( $from, $till );
		foreach ( $date_array as $key => $data ) {
			$date  = date_i18n( $date_dormat, $data['value'] );
			$html .= "<div class=\"bws_bkng_date_wrap bws_bkng_filter_date_" . esc_attr( $key ) . "\">
				<span class=\"bws_bkng_date_label\">" . esc_html( $data['label'] ) . "</span>
				<span class=\"bws_bkng_date\">" . esc_html( $date ) . "</span>
			</div><!-- .bws_bkng_date.bws_bkng_filter_date_<?php echo $key; ?> -->";
		}
		if ( $return )
			return $html;
		echo $html;
	}
}

/**
 * Outputs html structure for range slider
 * @since    0.1
 * @param    string   $key    Values for name attribute for hidden field
 * @param    array    $data   Additional attributes to form the structure of the slider
 * @return   void
 */
if ( ! function_exists( 'bws_bkng_numeric_range' ) ) {
	function bws_bkng_numeric_range( $key, $data ) {
		global $bws_bkng;
		$format_args = array( 'decimals' => $data['dec'] );
		$from        = bws_bkng_number_format( $data['from'], $format_args );
		$to          = bws_bkng_number_format( $data['to'],   $format_args );
		$min         = bws_bkng_number_format( $data['min'],  $format_args );
		$max         = bws_bkng_number_format( $data['max'],  $format_args ); ?>

		<div class="bws_bkng_slider_range_wrap">
			<p class="bws_bkng_filter_label"><?php echo esc_html( $data['label'] ) . ( empty( $data['measure'] ) ? '' : " (" . esc_html( $data['measure'] ) . ")" ); ?>:&nbsp;<span class="bws_bkng_range_view"><?php echo esc_html( "{$from} - {$to}" ); ?></span></p>
			<div class="bws_bkng_slider_range <?php echo BWS_BKNG::$hide_if_no_js; ?>" data-range="<?php echo esc_attr( json_encode( $data ) ); ?>">
				<div class="bws_bkng_slider_range_limit bws_bkng_slider_range_min"><?php echo esc_html( $min ); ?></div>
				<div class="bws_bkng_slider_range_limit bws_bkng_slider_range_max"><?php echo esc_html( $max ); ?></div>
			</div><!-- .bws_bkng_slider_range -->
			<?php $class = 'bws_bkng_range_value ' . BWS_BKNG::$hidden .  ' ' . BWS_BKNG::$show_if_no_js;
				$value   = $data['from'] . '-' . $data['to'];
				$name    = 'bws_bkng_' . $key ;
				echo $bws_bkng->get_text_input( compact( 'class', 'name', 'value' ) ); ?>
		</div><!-- .bws_bkng_slider_range_wrap -->
	<?php }
}

/**
 * Outputs HTML in the frontend for nice display
 * @since    0.1
 * @param    string   $key    Name attr value for form fields
 * @param    array    $data   Additional attributes for forming the structure of the list
 *                    format: array(
 *                        'label' => {string} - label for drop-down menu,
 *                        'type'  => {string} - list type. With one of the following 'select',
 *                                              'select_radio', 'select_locations' the list will include
 *                                              radioboxes - it is possible to choose only one of them,
 *                                              for any other - checkboxes, the possibility of multiple choice.
 *                        'value' => {array|string} - the current value (s) to mark the selected list items
 *                        'list'  => {array} - data in the format "value" => "label" to form list items
 *                        'placeholder' => {string} - placeholder for drop-down menu
 *                    )
 * @return   void
 */
if ( ! function_exists( 'bws_bkng_items_list' ) ) {
	function bws_bkng_items_list( $key, $data ) {
		global $bws_bkng;

		$single_choice_types  = array( 'select', 'select_radio', 'select_locations' );
		$dropdowns            = array( 'select', 'select_locations' );
		$simple_list   = "<ul class=\"bws_bkng_item_list bws_bkng_item_list_{$data['type']}\">%s</ul>";
		$list_item     = "<li>%s</li>";
		$list_items    = '';

		if ( in_array( $data['type'], $dropdowns ) ) {

			$placeholder = empty( $data['placeholder'] ) ? esc_attr( __( 'Choose an item', BWS_BKNG_TEXT_DOMAIN ) ) : esc_attr( $data['placeholder'] );

			if ( apply_filters( 'bws_bkng_select_as_list', false ) ) {
				$selected_label = $placeholder;
				$select_list = sprintf(
					$simple_list,
					"<li class=\"bws_bkng_selected_item\"><span class=\"bws_bkng_selected_item_text\" data-default-label=\"{$selected_label}\">%1\$s</span><ul>%2\$s</ul></li>"
				);
			} else {
				$name     = 'bws_bkng_' . $key;
				$id       = bws_bkng_sanitize_id( 'bws_bkng_select_item' );
				$selected = $data['value'];
				$options  = array( '-1' => $placeholder ) + $data['list']; ?>
				<p class="bws_bkng_filter_label"><?php echo esc_html( $data['label'] ); ?></p>
				<?php echo $bws_bkng->get_select( compact( 'name', 'id', 'selected', 'options' ) );
				return;
			}
		}
		$data['value'] = (array)$data['value'];

		foreach ( $data['list'] as $value => $label ) {

			$value            = urldecode( $value );
			$is_single_choice = in_array( $data['type'], $single_choice_types );
			$is_selected      = in_array( $value, $data['value'] );

			if ( in_array( $data['type'], $dropdowns ) && $is_selected )
				$selected_label = $label;

			$id    = bws_bkng_sanitize_id( "bws_bkng_item_{$key}_{$value}" );
			$name  = 'bws_bkng_' . $key  . ( $is_single_choice ? '' : '[]' );
			$attr  = $is_selected ? 'checked="checked"' : '';
			$func  = $is_single_choice ? 'get_radiobox' : 'get_checkbox';
			$after = "<span class=\"bws_bkng_label_text\">" . esc_html( $label ) . "</span>";
			$list_items .= sprintf( $list_item, $bws_bkng->$func( compact( 'id', 'name', 'value', 'after', 'attr' ) ) );
		} ?>

		<p class="bws_bkng_filter_label"><?php echo esc_html( $data['label'] ); ?></p>

		<?php if ( empty( $selected_label ) )
			printf( $simple_list, $list_items );
		else
			printf( $select_list, $selected_label, $list_items );
	}
}

/**
 * Outputs Reset and Submit buttons in search filter
 * @since    0.1
 * @param    void
 * @return   void
 */
if ( ! function_exists( 'bws_bkng_submit_buttons' ) ) {
	function bws_bkng_submit_buttons() {
		global $bws_bkng;

		$name  = '';
		$class = "bws_bkng_filter_button button button-primary";
		$value = __( 'Search', BWS_BKNG_TEXT_DOMAIN );
		$type  = 'submit';

		echo $bws_bkng->get_button_input( compact( 'name', 'class', 'value', 'type' ) );
	}
}
