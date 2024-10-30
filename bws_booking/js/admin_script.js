(function( $ ){

	var page = $( document );

	page.ready( function() {
		/**
		 * Displays the error on the plugin admin pages
		 * @type    {function}
		 * @access  private
		 * @param   {Object}    error_wrap
		 * @param   {string}    error
		 * @return  {void}
		 */
		function add_error( error_wrap, error ) {
			error_wrap.filter( '.' + bws_bkng.hidden ).removeClass( bws_bkng.hidden ).removeAttr( 'style' ).find( 'p' ).text( error );
		}

		/**
		 * Hides the specified error block
		 * @type    {function}
		 * @access  private
		 * @param   {Object}  error_wrap
		 * @return  {void}
		 */
		function hide_errors( error_wrap ) {
			error_wrap.not( '.' + bws_bkng.hidden ).addClass( bws_bkng.hidden ).find( 'p' ).text( '' );
		}

		/**
		 * Checks whether the currently managed attribute is taxonomy
		 * @type    {function}
		 * @uses    On the plugin Attributes edit page
		 * @access  private
		 * @param   {string}    type  The attribute type
		 * @return  {boolean}         true if the attribute is taxonomy, false otherwise
		 */
		function is_taxonomy( type ) {
			return -1 != $.inArray( type, [ 'select_checkboxes', 'select_radio', 'select', 'select_locations' ] );
		}

		/******
		 ****** General Google map handlers
		 *****/
		function draw_map( map_wrap ) {
			var id, lng_input, lat_input, error_wrap, parent;

			if ( typeof map_wrap == 'object' ) {
				id = map_wrap.attr( 'id' );
			} else {
				id = id || 'bkng_map_wrap';
				map_wrap = $( "#" + id + ':visible' );
			}

			if ( ! map_wrap.length )
				return;
			/*
			 * Init map
			 */
			parent     = map_wrap.closest( '.bkng_meta_input_wrap' );
			lng_input  = parent.find( '.bkng_longitude_input' );
			lat_input  = parent.find( '.bkng_latitude_input' );
			error_wrap = parent.find( '.bkng_js_errors:first' );

			bws_bkng_maps.add_single_map(
				id,
				{ center: { lat: lat_input.val(), lng: lng_input.val() } },
				function( result ) {
					if ( typeof result.error !== 'undefined' ) {
						add_error( error_wrap, result.error );
					} else {
						coordinates = bws_bkng_maps.parse_coordinates( result.geometry.location );
						lng_input.val( coordinates.lng );
						lat_input.val( coordinates.lat );
					}
				}
			);
		}

		if ( $.fn.datetimepicker )
			$.datetimepicker.setLocale( bws_bkng.locale )

		/**
		 * Remove "result" and "count" query parameters from the url in order to
		 * avoid displaying wrong messages after the page refreshing
		 */
		if ( window.location.href.match(/\&result=[a-z_\-]*\&count=[\d]+/gi ) )
			history.pushState( {}, '', window.location.href.replace( /\&result=[a-z_\-]*\&count=[\d]+/gi, '' ) );

		/**
		 * Handles the displaying of error data
		 */
		$( '.bkng_show_error_details_button' ).click( function() {
			$( this ).parent().next( '.bkng_error_details' ).toggle();
		});

		/* Add new interval row */
		$( '#bkng_add_interval' ).click( function( e ) {
			e.preventDefault();

			var new_input_index = 20;
			var per_days_price = $( '.bkng_price_by_days' ).filter(':last').attr( 'name' );
			var field_id = '';

			if ( per_days_price !== undefined ) {
				var match = $( '.bkng_price_by_days' ).filter(':last').attr( 'name' ).match( /\d+/g );
				field_id = Number( match[0] );
				new_input_index = Number( match[1] );
			}
			new_input_index += 2;

			$.ajax({
				type: 'POST',
				url:  ajaxurl,
				data: {
					action: 'bkng_get_new_interval',
					new_inputs_index : new_input_index,
					field_id : field_id
				},
				success: function( result ) {
					try {
						var data = $.parseJSON( result );
						$("#bkng_add_interval").before( data );
					} catch ( e ) {
						alert( e.name + ":\n" + e.message + '\n' + e.stack );
					}
				},
				error : function ( xhr, ajaxOptions, thrownError ) {
					alert( xhr.status );
					alert( thrownError );
				}
			});
		});

		/* del interval row */
		$( 'body' ).on( "click", ".bkng_row_cross", function( e ) {

			var match = $(this).prev( '.bkng_wrap_price_by_days' ).find('.bkng_price_by_days').attr( 'name' ).match( /\d+/g );
			var input_index = Number( match[1] );

			$.ajax({
				type: 'POST',
				url:  ajaxurl,
				data: {
					action: 'bkng_del_interval_row',
					del_input_index : input_index
				},
				success: function( result ) {
					try {
						var data = $.parseJSON( result );
						/* delete need interval */
						$( '.bkng_main_wrap_price_' + data ).remove();

					} catch ( e ) {
						alert( e.name + ":\n" + e.message + '\n' + e.stack );
					}
				},
				error : function ( xhr, ajaxOptions, thrownError ) {
					alert( xhr.status );
					alert( thrownError );
				}
			});
		});

		draw_map();

		/*
		 * Search the location by the address
		 */
		page.on( 'click', '.bkng_find_by_address_button', function( event ) {
			event = event || wiindow.event;
			event.preventDefault();

			parent     = $( this ).closest( '.bkng_meta_input_wrap' );
			lng_input  = parent.find( '.bkng_longitude_input' );
			lat_input  = parent.find( '.bkng_latitude_input' );
			error_wrap = parent.find( '.bkng_find_by_address_error' );

			hide_errors( error_wrap );

			bws_bkng_maps.find_on_single_map(
				/* Search will be executed by address */
				'address',
				/* Map wrapper */
				parent.find( '.bkng_map_wrap' ).attr( 'id' ),
				/* Map options */
				{ address: parent.find( '.bkng_address_input' ).val() },
				/* Callback function which will be called in the end of the search process */
				function( result ) {
					if ( typeof result.error !== 'undefined' ) {
						add_error( error_wrap, result.error );
						return;
					}

					coordinates = bws_bkng_maps.parse_coordinates( result.geometry.location );
					lng_input.val( coordinates.lng );
					lat_input.val( coordinates.lat );
				}
			);
		});

		/*
		 * Search the location by coordinates
		 */
		page.on( 'click', '.bkng_find_by_coordinates_button', function( event ) {
			event = event || wiindow.event;
			event.preventDefault();

			parent     = $( this ).closest( '.bkng_meta_input_wrap' ),
			lng_input  = parent.find( '.bkng_longitude_input' );
			lat_input  = parent.find( '.bkng_latitude_input' );
			error_wrap = parent.find( '.bkng_find_by_coors_error' );

			hide_errors( error_wrap );

			bws_bkng_maps.find_on_single_map(
				/* Search will be executed by coordinates */
				'location',
				/* Map wrapper */
				parent.find( '.bkng_map_wrap' ).attr( 'id' ),
				/* Map options */
				{ location: { lat: lat_input.val(), lng: lng_input.val() } },
				/* Callback function which wiil be called in the end of the search process */
				function( result ) {
					if ( typeof result.error !== 'undefined' )
						add_error( error_wrap, result.error );
				}
			);
		});

		$( ".bkng_error_wrap" ).find( '.notice-dismiss' ).click( function( event ) {
			hide_errors( $( this ).parent() );
		});

		/*****************************/
		/***** Edit Products Page ****/
		/*****************************/
		var attributes = $( '#bws_booking_' + $( '#post_type' ).val() + '_attributes' );

		if ( attributes.length ) {

			/****
			 **** General handlers
			 ***/

			page.click( function( event ) {
				event = event || window.event;

				/* Hide the terms taxonomies lists */
				$( '.bkng_expanded' ).not( $( event.target ).closest( '.bkng_expanded' ) ).each( function() {
					var list = $( this );
					list.addClass( 'bkng_collapsed' ).removeClass( 'bkng_expanded' );
					hide_errors( list.find( '.bkng_ajax_error' ) );
					list.find( '.bkng_new_taxonomy_input' ).val( '' );
				});

				/*
				 * Hide the location input fields
				 */
				if ( typeof event.target.gm_id == 'undefined' ) {
					$( '.bkng_map_expanded' ).not( $( event.target ).closest( '.bkng_map_expanded' ) ).each( function() {
						var map = $( this );
						map.removeClass( 'bkng_map_expanded' )
							.children( '.bkng_toggle_displaying' )
								.addClass( bws_bkng.hidden );
						map.find( '.bkng_error_wrap' ).not( '.' + bws_bkng.hidden ).addClass( bws_bkng.hidden ).find( 'p' ).text( '' );
					});
				}
			});

			/****
			 **** Variations list handlers
			 ***/

			/*
			 * Save variation data before switching to another
			 */
			$( '.bkng_variations_tabs a' ).click( function( event ) {
				var $this  = $( this ),
					action = $this.attr( 'data-action' ),
					id     = $this.attr( 'data-id' );
				/* Saves the post data before switching between variations */
				if ( action != 'delete' ) {
					event  = event || window.event;
					event.preventDefault();
					$( 'input[name="bkng_variation"]' ).val( id );
					$( 'input[name="bkng_variation_action"]' ).val( action );
					$( '#publish[name="save"]' ).trigger( 'click' );
				}
			});

			/*****
			 ***** Preferences tabs handlers
			 *****/
			/*
			 * Tabs init
			 */
			var tabs         = $( '#bkng_tabs_wrap' ),
				tabs_options = {
					activate: function( event, ui ) {

						/* Init the extras tree after switching th the "Extras" tab */
						if ( ui.newPanel.selector != '#bkng_tab_extras' || trees )
							return;

						trees = $( '.bkng_tree' ).each( function() {
							var own_class = $( this ).attr( 'class' ).match( /bkng_[\w\d]+_tree/ );
							if ( own_class[0] )
								new BwsBkngTree( '.' + own_class[0] );
						});
					}
				},
				trees;

			tabs.tabs( tabs_options );

			/*
			 * Change the "Attribute" tab content after changing the category of product
			 * via AJAX call
			 */
			var post_id         = $( 'input[name="post_ID"]' ).val(),
				variation_id    = $( 'input[name="bkng_edited_variation"]' ).val(),
				nonce           = $( 'input[name="bkng_get_attributes_nonce"]' ).val(),
				attributes_wrap = $( '#bkng_tab_attributes .bkng_tab_content' ),
				tab_links       = $( '#bkng_tab_link_attributes, #bkng_tab_link_price' ),
				rent_select     = $( '.bkng_rent_interval_row' ).find( 'select' ),
				cat_slug;

			page.on( 'change', 'input[name="bkng_post_meta[bws_bkng_categories]"]', function() {
				var cat_list = $( 'input[name="bkng_post_meta[bws_bkng_categories]"]' ).attr( 'disabled', true );
				cat_slug = cat_list.filter( ':checked' ).val();
				$.ajax({
					type: 'POST',
					url:  ajaxurl,
					data: {
						action:         'bkng_get_attributes',
						bkng_post_id:   post_id,
						bkng_cat_slug:  cat_slug,
						bkng_variation: variation_id,
						bkng_nonce:     nonce
					},
					success: function( result ) {
						try {
							var data = $.parseJSON( result );
							attributes_wrap.html( '' ).html( data.attributes );

							if ( rent_select.val() != data.rent_interval )
								rent_select.val( data.rent_interval );

							cat_list.attr( 'disabled', false );
							tab_links.addClass( 'bkng_animated' );
							setTimeout(
								function() {
									tab_links.removeClass( 'bkng_animated' )
								},
								1000
							);
						} catch ( e ) {
							alert( e.name + ":\n" + e.message + '\n' + e.stack );
						}
					},
					error : function ( xhr, ajaxOptions, thrownError ) {
						alert( xhr.status );
						alert( thrownError );
					}
				});
			});

			/*****
			 ***** Meta lists handlers
			 ****/

			/*
			 * Add new taxonomy term via AJAX call
			 */
			page.on( 'click', '.bkng_add_meta_submit', function( event ) {
				event = event || wiindow.event;
				event.preventDefault();

				var term_input  = $( 'input[name=bkng_term_name_' + this.name + ']' ),
					nonce_field = $( 'input[name=bkng_term_nonce_' + this.name + ']' ),
					error_wrap  = $( '#bkng_ajax_response_' + this.name ),
					term_list   = $( '#' + this.name + '_list' ),
					input_type  = $( 'input[name=bkng_term_display_type_' + this.name + ']' ).val();

					hide_errors( error_wrap );

				$.ajax({
					type: 'POST',
					url:  ajaxurl,
					data: {
						action: 'bkng_add_term',
						tax:    this.name,
						name:   term_input.val(),
						nonce:  nonce_field.val()
					},
					success: function( result ) {
						try {
							var term_data       = $.parseJSON( result ),
								all_counter     = term_list.next().find( '.bkng_all_count' ),
								checked_counter = term_list.next().find( '.bkng_checked_count' ),
								all, checked, name_postfix;

							switch( input_type ) {
								/* change the counters */
								case 'checkbox':
									all     = term_list.find( 'input[type=checkbox]' ).length + 1;
									checked = term_list.find( 'input[type=checkbox]:checked' ).length + 1;
									name_postfix = '[]';

									break;
								/* change the label */
								case 'radio':
									term_list.find( 'input[type=radio]' ).attr( 'checked', false );
									all     = '';
									checked = term_data.name;
									name_postfix = '';
									break;
								default:
									break;
							}

							if ( ! checked )
								return false;

							term_list.append( '<li><label class="bkng_meta_input"><input name="bkng_post_meta[' + term_data.taxonomy + ']' + name_postfix + '" value="' + term_data.slug + '" type="' + input_type + '" checked="checked" />' + term_data.name + '</label></li>' )
								.removeClass( bws_bkng.hidden )
								.scrollTop( term_list.prop( "scrollHeight" ) ).find( 'input' ).trigger( 'change' );

							term_input.val( '' );

							if ( !! checked ) {
								all_counter.text( all );
								checked_counter.text( checked );
							}
						} catch ( e ) {
							add_error( error_wrap, result );
						}
					},
					error : function ( xhr, ajaxOptions, thrownError ) {
						alert( xhr.status );
						alert( thrownError );
					}
				});
			});

			/*
			 * Show/hide the terms taxonomies lists
			 */
			page.on( 'click', '.bkng_placeholder', function() {
				var placeholder  = $( this ),
					current_list = placeholder.closest( '.bkng_meta_input_wrap' );

				if ( current_list.hasClass( 'bkng_collapsed' ) ) {
					current_list.removeClass( 'bkng_collapsed' ).addClass( 'bkng_expanded' );
				} else {
					current_list.addClass( 'bkng_collapsed' ).removeClass( 'bkng_expanded' );
				}
			});

			/*
			 * Change the checked terms counter
			 */
			page.on( 'click', '.bkng_meta_list input[type=checkbox]', function() {
				var term_list = $( this ).closest( '.bkng_meta_list' ),
					counter   = term_list.next().find( '.bkng_checked_count' ),
					count     = term_list.find( 'input[type=checkbox]:checked' ).length;
					counter.text( count );
			});

			/*
			 * Change the title of the chosen term.
			 */
			page.on( 'click', '.bkng_meta_list input[type=radio]', function() {
				var input      = $( this );
					term_list = input.closest( '.bkng_meta_list' ),
					text      = input.parent().text(),
					counter   = term_list.next().find( '.bkng_checked_count' );
					counter.text( text );
			});

			/*
			 * Show/hide the location input fields
			 */
			page.on( 'click', '.bkng_show_map_button', function( event ) {
				event = event || window.event;
				event.preventDefault();

				var parent   = $( this ).closest( '.bkng_meta_input_wrap' ),
					map_wrap = parent.find( '.bkng_map_wrap' );

				parent.toggleClass( 'bkng_map_expanded' );
				parent.find( '.bkng_toggle_displaying' ).toggleClass( bws_bkng.hidden );

				/* Initialize the map during the first showing only */
				if ( map_wrap.is( ':visible' ) && map_wrap.html() == '' )
					draw_map( map_wrap );
			});
		}

		/*****
		 ***** Init the gallery handler
		 *****/
		var galleries = $( '.bkng_post_gallery' );
		if ( galleries.length ) {
			galleries.each( function() {
				new BwsBkngGallery( $( this ) );
			});
		}

		/*****************************/
		/***** Attributes Page ****/
		/*****************************/
		var type_list = $( '.bkng_display_type' );

		/* Fires if the edit attribute form is on the page */
		if ( type_list.length ) {

			var attribute_options   = $( '.bkng_attribute_option' ),
				number_settings     = $( '.bkng_number_settings' ),
				old_type = new_type = type_list.val();

			/*
			 * Show/hide additional settings in the "Type" column
			 */
			type_list.change( function() {
				new_type = this.value;
				attribute_options.not( '.' + bws_bkng.hidden ).addClass( bws_bkng.hidden );
				switch ( this.value ) {
					case 'number':
						number_settings.removeClass( bws_bkng.hidden );
						break;
					default:
						break;
				}
			});

			/*
			 * Show warning message before form submit
			 */
			$( '.bkng_edit_form_submit' ).click( function( event ) {
				event = event || window.event;
				event.preventDefault();

				var old_is_taxonomy          = is_taxonomy( old_type ),
					new_is_taxonomy          = is_taxonomy( new_type ),
					is_still_same            = old_type == new_type,
					is_another_taxonomy_type = old_is_taxonomy && new_is_taxonomy,
					is_not_taxonomy          = ! old_is_taxonomy && ! new_is_taxonomy;

				if ( is_still_same || is_another_taxonomy_type || is_not_taxonomy )
					$( this ).closest( 'form' ).submit();
				else if ( confirm( bws_bkng.save_attribute_confirm_message.replace( /%s/, $( '.bkng_attribute_label' ).val() ) ) )
					$( this ).closest( 'form' ).submit();
			});

			/*
			 * Remove error highliting from <inputs>
			 */
			$( '.bkng_input_error' ).focusout( function() {
				$( this ).removeClass( 'bkng_input_error' );
			});
		}

		$( '.bws_bkng-value-delete input' ).addClass( 'bws_bkng-value-delete-check' );
		$( '.bws_bkng-value-delete label' ).click( function() {
			/* clear value */
			$( this ).parent().parent().children( 'input.bws_bkng-add-options-input' ).val( '' );
			/* hide field */
			$( this ).parent().parent().hide();

			if ( 'function'  ==  typeof bws_show_settings_notice ) {
				bws_show_settings_notice();
			}
		} );

		$( '#bws_bkng-select-type' ).on( 'change', function() {
			type_value = $( this ).val();
			$( '.bws_bkng-fields-container, .bws_bkng-time-format, .bws_bkng-date-format, .bws_bkng-maxlength, .bws_bkng-rows, .bws_bkng-cols' ).hide();

			if ( '3' == type_value || '4' == type_value || '5' == type_value ) {
				$( '.bws_bkng-fields-container' ).show();
			} else {
				if ( '6' == type_value || '8' == type_value ) {
					$( '.bws_bkng-date-format' ).show();
				}
				if ( '7' == type_value || '8' == type_value ) {
					$( '.bws_bkng-time-format' ).show();
				}
				if ( '1' == type_value || '9' == type_value ) {
					$( '.bws_bkng-maxlength' ).show();
				}
				if ( '2' == type_value ) {
					$( '.bws_bkng-rows, .bws_bkng-cols, .bws_bkng-maxlength' ).show();
				}
			}
		} ).trigger( 'change' );
		/* Add additional fields for checkbox, radio, select */
		$( '#bws_bkng-add-field' ).click( function() {
			/* Clone previous input */
			var lastfield = $( '.bws_bkng-drag-values' ).last().clone( true );
			/* remove hidden input */
			lastfield.children( 'input.hidden' ).remove();
			/* clear textfield */
			lastfield.children( 'input.bws_bkng-add-options-input' ).val( '' );
			/* Insert field before button */
			lastfield.clone( true ).removeClass( 'hide-if-js' ).show().insertAfter( $( '.bws_bkng-drag-values' ).last() );
		} );
		/* Sortable table settings */
		if ( $.fn.sortable ) {
			/* Drag n drop values list */
			$( '.bws_bkng-drag-values-container' ).sortable( {
				itemSelector: 'div',
				/* Without container selector script return error */
				containerSelector: '.bws_bkng-drag-values-container',
				handle: '.bws_bkng-drag-field',
				placeholder: 'bws_bkng-placeholder',
				stop: function( event, ui ) {
					if ( 'function' == typeof bws_show_settings_notice ) {
						bws_show_settings_notice();
					}
				}
			} );
		}

		/*****************************/
		/***** TAXONOMIES Page ****/
		/*****************************/

		var timepicker = $( '.bkng_timepicker' );
		if ( timepicker.length ) {
			timepicker.datetimepicker({
				datepicker : false,
				format     : bws_bkng.time_format
			});
		}

		/*****************************/
		/***** Profile Page **********/
		/*****************************/
		var list_profile_form = $( '#bkng_user_profile_form' );
		if ( list_profile_form.length ) {
			$( 'a[data-action="view"]' ).click( toggle_order );

			$( '[class^="bkng_order_id_"]' ).each( function( i ) {
				var class_name = '';
				if ( i % 2 ) {
					class_name = 'bkng-tr-even';
				} else {
					class_name = 'bkng-tr-odd';
				}
				$( this ).addClass( class_name );
			} );

			function toggle_order( e ) {
				e.preventDefault();

				var order_id = e.target.dataset['orderId'],
					post_type = $( '[name="post_type"]' ).val(),
					nonce = $( 'input#_wpnonce' ).val(),
					tr = $( 'tr.bkng_order_id_' + order_id + ':not( .bkng-opened-order )' ),
					tr_children = tr.children(),
					order = $( '.bkng-opened-order.' + 'bkng_order_id_' + order_id ),
					amount_of_col = list_profile_form.find( 'thead > tr > *' ).length;
					loader_html = '<td colspan="' + amount_of_col + '" class="bkng-loader-wrap"><div class="bkng-loader"><div></div><div></div><div></div><div></div></div></td>';

				if ( order.length ) {
					if ( order.is( ':hidden' ) ) {
						tr_children.hide();
						order.show();
					} else {
						tr_children.show();
						order.hide();
					}
				} else {
					tr_children.hide();
					tr.append( loader_html );

					$.ajax( {
						type: 'POST',
						url:  ajaxurl,
						data: {
							action:         'bkng_handle_profile_ajax',
							class:			list_args.class, // list_args is a global var that is beigh outputed by 'ajax' parameter in tables class __construct
							post_type:  	post_type,
							bkng_order_id:  order_id,
							bkng_nonce:     nonce
						},
						success: function( response ) {
							tr.find( '.bkng-loader-wrap' ).remove();
							order = $( response );
							order.addClass( tr.hasClass( 'bkng-tr-odd' ) ? 'bkng-tr-odd' : 'bkng-tr-even' );
							tr.after( order );
							order.delegate( 'button[data-action="close"]', 'click', toggle_order );
						},
						error : function ( xhr, ajaxOptions, thrownError ) {
							alert( xhr.status );
							alert( thrownError );
						}
					} );
				}
			}
		}

		/*****************************/
		/***** ORDERS Page ****/
		/*****************************/
		var list_orders_form = $( '#bkng_orders_form' );
		if ( list_orders_form.length ) {
			$( '#doaction, #doaction2' ).on( 'click', function( event ) {
				event = event || window.event;
				event.preventDefault();

				if ( confirm( bws_bkng.delete_order_confirm_message ) )
					list_orders_form.submit();
			});
			var show_confirm = true;
			$( '.delete a' ).on( 'click', function( event ) {
				if ( show_confirm ) {
					if ( confirm( bws_bkng.delete_order_confirm_message ) ) {
						show_confirm = false;
						$( this ).trigger( 'click' );
					} else {
						show_confirm = true;
						event = event || window.event;
						event.preventDefault();
					}
				}
			});
		}

		/*****************************/
		/***** SINGLE ORDER Page ****/
		/*****************************/
		$.fn.extend({
			recalculate_order: function() {
				var total, subtotal,
					date_formatter = new DateFormatter,
					date_format    = 'd.m.Y H:i';
				function count_summaries( id ) {
					var row = $( 'tr[data-id="' + id + '"]' );

					if ( ! row.length )
						return;

					total = subtotal = parseFloat( bws_bkng_products[ id ].price ) * parseInt( bws_bkng_products[ id ].quantity ) * Math.ceil( ( bws_bkng_products[ id ].rent_interval.till - bws_bkng_products[ id ].rent_interval.from ) / bws_bkng_products[ id ].rent_interval.step );

					bws_bkng_products[ id ].subtotal = parseFloat( subtotal );
					bws_bkng_products[ id ].total    = parseFloat( total );

					row.find( '.column-subtotal .bws_bkng_price' ).text( bws_bkng_number_format( subtotal ) );
					row.find( '.column-total .bws_bkng_price' ).text( bws_bkng_number_format( total ) );

					return row;
				}

				switch( this.attr( 'type' ) ) {
					case 'text':
						var is_from_date  = this.attr( 'name' ).match( /from/ ),
							change_values = false,
							raw_from, raw_till,
							from, till, step,
							pair_input, temp, now;

						if ( is_from_date ) {
							pair_input = $( 'input[name="'+ this.attr( 'name' ).replace( "from", "till" ) + '"]' );
							raw_from   = this.val();
							raw_till   = pair_input.val();
						} else {
							pair_input = $( 'input[name="'+ this.attr( 'name' ).replace( "till", "from" ) + '"]' );
							raw_from   = pair_input.val();
							raw_till   = this.val();
						}

						from = Math.floor( Date.parse( raw_from ) ) / 1000;
						till = Math.floor( Date.parse( raw_till ) ) / 1000;
						now  = Math.floor( Date.now() ) / 1000;

						/**
						 * If the user selected the time interval incorrectly
						 * Rearrange dates
						 */
						if ( from > till ) {
							temp = from;
							from = till;
							till = temp;
							change_values = true;
						}

						/**
						 * If the user selected the time interval started from the past
						 * Make rent interval start from the tomorrow and expand to the minimum rent interval step
						 */
						if ( from < now ) {
							from = now + 86400;
							change_values = true;
						}

						/**
						 * Change datetimepickers value
						 */
						if ( change_values ) {
							raw_from = date_formatter.formatDate( new Date( parseInt( from * 1000 ) ), date_format );
							raw_till = date_formatter.formatDate( new Date( parseInt( till * 1000 ) ), date_format );

							if ( is_from_date ) {
								this.val( raw_from );
								pair_input.val( raw_till );
							} else {
								pair_input.val( raw_from );
								this.val( raw_till );
							}
						}

						for ( var id in bws_bkng_products ) {

							bws_bkng_products[ id ].rent_interval.from = from;
							bws_bkng_products[ id ].rent_interval.till = till;

							count_summaries( id );
						}
						break;

					case 'number':
						var id       = this.closest( 'tr' ).attr( 'data-id' ) || 0
							max      = parseInt( this.attr( 'max' ) ),
							quantity = parseInt( this.val() );

						if ( quantity > max )
							quantity = max;
						else if ( quantity < 0 || isNaN( quantity ) )
							quantity = 1;

						this.val( quantity );
						bws_bkng_products[ id ].quantity = quantity;

						count_summaries( id );

						break;

					default:
						return;
				}

				subtotal = 0;

				for ( var id in bws_bkng_products ) {
					subtotal += parseFloat( bws_bkng_products[ id ].subtotal );
				}

				total = subtotal;

				$( '#bkng_order_subtotal .bws_bkng_price' ).text( bws_bkng_number_format( subtotal ) );
				$( '#bkng_order_total .bws_bkng_price' ).text( bws_bkng_number_format( total ) );
				return this;
			}
		});

		var client_GMT_offset = new Date().getTimezoneOffset() * 60; /* seconds */
		$( '.bws_bkng_datepicker' ).each( function() {
			var $this   = $( this ),
				raw_val = $this.val(),
				value = '' != raw_val ? ( isNaN( parseInt( raw_val ) ) ? new Date() : new Date( parseInt( raw_val ) * 1000 ) ) : new Date();

				var GMT_value   = new Date( ( parseInt( raw_val )) * 1000 );

				$this.datetimepicker({
				format       : 'Y.m.d H:i',
				minDate      : 0,
				value        : raw_val,
				onClose: function(){
					this.setOptions({
						value: value
					})
				}
			}).change( function() {
				$( this ).recalculate_order();
			});
		});

		$( '.bkng_quantity_input:not([readonly])' ).on( 'keyup change click', function() {
			$( this ).recalculate_order();
		});
	}).on( 'bkng_reset', function() {
		$( '.bkng_longitude_input' ).val( bws_bkng.default_lng );
		$( '.bkng_latitude_input' ).val( bws_bkng.default_lat );
		$( '.bkng_address_input' ).val( bws_bkng.default_addr );
		$( '.bkng_work_from' ).val( bws_bkng.default_work_from );
		$( '.bkng_work_till' ).val( bws_bkng.default_work_till );
		$( 'input[type="checkbox"][name^="bkng_agency_working_hours"]' ).attr( 'checked', false );
	}).ajaxSuccess( function( event, request, settings ) {
		try {
			var raw = settings.data.split( '&' ), data = {}, temp;
			for( var i = 0; i < raw.length; i++ ) {
				var temp = raw[ i ].split( '=' );
				data[ temp[0] ] = decodeURIComponent( temp[1] ? temp[1].replace( /\+/g, ' ' ) : temp[1] );
			}

			if ( data.post_type && data.post_type == bws_bkng.post_type )
				page.trigger( 'bkng_reset' );

		} catch( exception ) {
			//
		}
	});
})( jQuery );
