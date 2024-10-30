(function( $ ){
	var page = $( document );
	page.ready( function() {

		function add_to_cart( data, link, callback ) {
			var loader = $( '<span class="bas_bkng_icon bas_bkng_loader dashicons dashicons-image-rotate"></span>' );
			$.ajax({
				type: 'POST',
				url :  bws_bkng.ajaxurl,
				data: data,
				beforeSend: function() {
					loader.insertAfter( link );
				},
				success: function( result ) {
					try {
						result = JSON.parse( result );
						callback( result );
					} catch( e ) {
						alert( e.name + ":\n" + e.message + '\n' + e.stack + '\n\n' + result );
					}
					loader.remove();
				},
				error : function ( xhr, ajaxOptions, thrownError ) {
					alert( xhr.status );
					alert( thrownError );
				}
			});
		}

		$( 'input:reset.bws_bkng_filter_button' ).click( function( event ){
			var form = $( this ).closest( 'form' );
			form.find( 'input:radio, input:checkbox' ).attr( 'checked', false ).prop( 'checked', false ).trigger( 'change' );
			form.find( 'input:text, textarea' ).attr( 'value', '' ).prop( 'value', '' ).trigger( 'change' );
			var options = form.find( 'option' );
			options.not( '[value="-1"]' ).attr( 'selected', false ).prop( 'selected', false );
			options.filter( '[value="-1"]' ).attr( 'selected', 'selected' ).prop( 'selected', true ).trigger( 'change' );
			page.trigger( 'bkng_reset' );
		});

		/**
		 * Init numbers slider
		 */
		var sliders = $( '.bws_bkng_slider_range_wrap' );
		if ( sliders.length ) {
			sliders.each( function() {
				var $this  = $( this ),
					slider = $this.find( '.bws_bkng_slider_range' ),
					view   = $this.find( '.bws_bkng_range_view' ),
					input  = $this.find( '.bws_bkng_range_value' ),
					data   = $.parseJSON( slider.attr( 'data-range' ) );

				function change_values( values ) {
					view.html( bws_bkng_number_format( values[0], data['dec'] ) + " - " + bws_bkng_number_format( values[1], data['dec'] ) );
					input.attr( 'value', values[0] + "-" + values[1] );
					input.val( values[0] + "-" + values[1] );
				}

				slider.slider({
					range  : true,
					step   : 1 / Math.pow( 10, data.dec ),
					min    : data.min,
					max    : data.max,
					values : [ data.from, data.to ],
					slide  : function( event, ui ) { change_values( ui.values ); },
					change : function( event, ui ) { change_values( ui.values ); }
				});

				/**
				 * Add reset handler
				 */
				page.on( 'bkng_reset', function() {
					slider.slider( 'values', [ data.min, data.max ] );
					slider.trigger( 'slidechange' );
				});
			});
		}

		/**
		 * Change the size of search form's thumbnail in slider
		 */
		var search_form_image = $( '.bkng_form_img' );
		if ( search_form_image.length ) {
			$( window ).resize( function() {
				if ( $( this ).width() < 783 )
					return;

				search_form_image.each( function() {
					var $this       = $( this ),
						wrap_offset = Math.abs( parseInt( $this.parent().css( 'margin-top' ) ) ) - 15,
						margin_top  = wrap_offset - $this.find( 'img' ).height() / 2;
						if ( margin_top < 0 )
							margin_top = 0;
					$this.css({ 'margin-top': margin_top + 'px' });
				});
			}).trigger( 'resize' );
		}

		/*
		 * Init the datepicker
		 */
		var datetimepicker = $( '.bws_bkng_filter_datetimepicker' );
		if ( datetimepicker.length ) {
			var tomorrow = new Date();
				tomorrow.setDate( tomorrow.getDate() + 1 );

			if ( tomorrow.getMinutes() ) {
				tomorrow.setMinutes( 60 );
			}

			var after_tomorrow = new Date( tomorrow.getTime() + 60 * 60 * 24 * 1000 );

			$.datetimepicker.setLocale( bws_bkng.locale );

			datetimepicker.each( function() {
				var datepicker  = $( this ).find( ".bws_bkng_datepicker" ),
					raw_val     = datepicker.val(),
					default_val = datepicker.attr( 'name' ).match( /(from|till)/ )[1] == 'from' ? tomorrow : after_tomorrow ,
					value       = raw_val ? new Date( raw_val ) : default_val;

				if ( value.getMinutes() ) {
					value.setMinutes( 60 );
				}
				var format = 'hide' == datepicker.attr( 'data-display-time' ) ? bws_bkng.date_format : bws_bkng.date_format + ' ' + bws_bkng.time_format;
				datepicker.datetimepicker( {
					format       : format,
					minDate      : tomorrow,
					defaultDate  : default_val,
					value        : value,
					timepicker   : ( 'hide' == datepicker.attr( 'data-display-time' ) ? false : true ),
					onSelectDate: function( e ) {
						this.setOptions( {
							value: e
						} )
						/**
						* Compare current dates
						*/
						let date = $( "input[name*='from']" ).val();
						date = new Date( date );
						let nextDate = new Date();
						nextDate = new Date( date.getTime() + 60 * 60 * 24 * 1000 );
						$( "input[name*='till']" ).datetimepicker( {
							minDate      : nextDate,
						} )
						var sepatatorCurrent = '';
						let separator =  [ ' ', '-', '/' ];
						for ( let i = 0; i < separator.length; i++ ) {
							let index = format.indexOf( separator[i] );
							if ( -1 != index ) {
								sepatatorCurrent = separator[i];
								formatArray = format.split( separator[i] );
							}
						}
						for ( let i = 0; i < formatArray.length; i++  ) {
							switch ( formatArray[i] ) {
								case 'd': formatArray[i] = ( "0" + nextDate.getDate() ).slice( -2 );
								break;
								case 'm': formatArray[i] = ( "0" + (nextDate.getMonth() + 1 ) ).slice( -2 );
								break;
								case 'M': formatArray[i] = nextDate.toLocaleString( 'default', { month: 'short' } );
								break;
								case 'Y': formatArray[i] = nextDate.getFullYear();
								break;
							}
						}
						let newDate = '';
						for ( let i = 0; i < formatArray.length; i++  ) {
							newDate += formatArray[i];
							if ( i != formatArray.length - 1 ) {
								newDate += sepatatorCurrent;
							}
						}
						let dateTo = $( "input[name*='till']" ).val();
						dateTo = new Date( dateTo );
						if ( date >= dateTo ) {
							$( "input[name*='till']" ).datetimepicker( {
								value      : newDate,
							} )
						}
					},
					onChangeDateTime: function( e ) {
						this.setOptions( {
							value: e
						} )
					},
					onClose: function( e ) {
						this.setOptions( {
							value: e
						} )
					}
				} );
				/**
				 * Add reset handler
				 */
				page.on( 'bkng_reset', function() {
					var date_formatter = new DateFormatter;
					datepicker.attr( 'value', date_formatter.formatDate( default_val, bws_bkng.date_format + ' ' + bws_bkng.time_format ) );
				} );
			} );
		}
		/**
		 * Handle the custom dropdown list in the search filter
		 */
		var custom_selects = $( '.bws_bkng_item_list_select, .bws_bkng_item_list_select_locations' );
		if ( custom_selects.length ) {
			custom_selects.find( 'input:radio' ).change( function() {
				var $this = $( this ),
					list_item = $this.closest( '.bws_bkng_selected_item' );

				$this.closest( '.bws_bkng_item_list' ).find( '.bws_bkng_selected_item_text' ).text( $( this ).next( '.bws_bkng_label_text' ).text() );
				list_item.addClass( 'bws_bkng_collapsed' );

				setTimeout( function() {
					list_item.removeClass( 'bws_bkng_collapsed' );
				}, 200 );
			});
			page.on( 'bkng_reset', function() {
				var text_block = custom_selects.find( '.bws_bkng_selected_item_text' );
				text_block.text( text_block.attr( 'data-default-label' ) );
			});
		}

		/*
		 * Generates a search query string by parsing form data
		 * and redirects to the page with the specified parameters
		 */
		$( '.bws_bkng_search_products_form' ).on( 'submit', function( event ) {

			var form           = $( this ).closest( 'form' ),
				sort_queries   = [ bws_bkng.category, 'show', 'orderby', 'order', 'view' ],
				redirect_url   = bws_bkng.home_url + '/',
				submit_allowed = true,
				error_wrap     = form.prev( '.bkng_error_wrap' ),
				current_time   = parseInt( new Date().getTime() / 1000 ),
				matches, value, key, regexp, data, error;
			/**
			 * Displays error messages on the post edit page
			 * @type   {function}
			 * @param  {Object}   error_wrap  jQuery object - the block where the current error message should be inserted
			 * @param  {string}   error       Delay in milliseconds after which to hide the error message
			 * @return {void}
			 */
			function add_filter_error( error ) {
				if ( error_wrap.length ) {
					error_wrap.removeClass( bws_bkng.hidden ).find( '.bkng_error_content' ).append( '<p>' + error + '</p>' );
				}
			}

			/**
			 * Hides the error message on the post edit page
			 * @type   {function}
			 * @param  {void}
			 * @return {void}
			 */
			function hide_filter_errors() {
				if ( error_wrap.length )
					error_wrap.addClass( bws_bkng.hidden ).find( 'p' ).text( '' );
			}

			hide_filter_errors();
			/**
			 * a search query is formed from the data of filling out the form
			 */
			data = form.serializeArray().reduce( function( obj, item ) {

				matches = item.name.match( /bws_bkng_(from|till)/ );
				if ( matches ) {
					key   = matches[1];
					value = item.value.match( /^\d+$/ ) ? item.value : Date.parse( item.value ) / 1000; /* get secconds */

					if ( ! value || isNaN( value ) ) {
						add_filter_error( bws_bkng.wrong_date );
						submit_allowed = false;
					}

				} else {
					if( -1 != item.name.indexOf( '[' ) ) {
						/* for list items, replace the field 'bws_bkng_search[taxonomy_slug][term_slug]' with 'taxonomy_slug' */
						key   = item.name.replace( /bws_bkng_\[([^\[\]]+)\]/g, '$1' );
						key   = key.replace( /([^\[\]]+)\[([^\[\]]+)\]/g, '$1' );
						value = item.value;
					} else {
						key   = item.name.replace( /bws_bkng_(.+)/g, '$1' );
						value = item.value;
					}
				}

				if ( value && value != '-1'  ) {
					obj[ key ] = typeof obj[ key ] == 'undefined' ? value : obj[ key ] + ',' + value;
				}

				return obj;
			}, {} );

			//console.log(data);
			//event.preventDefault();
			//submit_allowed = false;
			//return false;

			/**
			 * Add queries for sorting, the current page number and the original product category
			 */
			sort_queries.forEach( function( key ) {

				if ( ! bws_bkng.is_custom_permalinks && bws_bkng.category == key )
					regexp = new RegExp( '&' + bws_bkng.category + '=([\\w-]+)' );
				else if ( !! bws_bkng.is_custom_permalinks )
					regexp = new RegExp( '\/' + key + '\/([\\w-]+)' );
				else
					regexp = new RegExp( '&' + key + '\=([\\w-]+)' );

				matches = window.location.href.match( regexp );

				if ( matches && typeof data[ key ] == 'undefined' )
					data[ key ] = matches[1];

			});

			/**
			 * Analyzing user request data
			 */
			data = BwsBkngHooks.apply_filters( 'products_search_form_data', data );

			if ( typeof data[ bws_bkng.category ] == 'undefined' ) {
				if( form.find( 'select[name*="bws_bkng_categories"]' ).length > 0 ) {
					submit_allowed = false;
					add_filter_error( bws_bkng.category_not_selected );
				}
			}
			if ( typeof data['location'] == 'undefined' ) {
				if( form.find( 'input[name*="bws_bkng_location"]' ).length > 0 ) {
					submit_allowed = false;
					add_filter_error( bws_bkng.location_not_selected );
				}
			}
			if ( data['till'] - data['from'] < 3600 ) {
				add_filter_error( bws_bkng.wrong_date );
				submit_allowed = false;
			} else if ( data['from'] < current_time || data['till'] < current_time ) {
				add_filter_error( bws_bkng.past_date );
				submit_allowed = false;
			}

			/**
			 * Form submit
			 */
			if ( BwsBkngHooks.apply_filters( 'products_search_submit_allowed', submit_allowed ) ) {
				return true;
			} else {
				error = BwsBkngHooks.apply_filters( 'products_search_error', false );
				if ( error ) {
					add_filter_error( error );
				}
			}
			return false;
		});

		/**
		 * Initializing the library functionality for the product image gallery
		 */
		if ( typeof BwsBkngGallery !== 'undefined' ) {
			var galleries = $( '.bws_bkng_gallery' );

			if ( galleries.length ) {
				galleries.each( function() {
					new BwsBkngGallery( $( this ) );
				});
			}
		}

		/**
		 * displays a map for the single-location attribute
		 */
		var show_map_link = $( '.bws_bkng_show_map_link' );
		if ( show_map_link.length && typeof bws_bkng_maps !== 'undefined' ) {
			if( '' != show_map_link.attr( 'data-display' ) ){
				var target      = $( '#' + show_map_link.attr( 'data-target' ) );
				if ( target.length ) {
					bws_bkng_maps.add_single_map(
						show_map_link.attr( 'data-target' ),
						{ center: { lat: target.attr( 'data-lat' ), lng: target.attr( 'data-lng' ) } }
					);
				}
			}
			show_map_link.click( function( event ) {
				event = event || window.event;
				event.preventDefault();

				var $this      = $( this ),
					target_id  = $this.attr( 'data-target' ),
					target      = $( '#' + target_id );

				if ( ! target.length )
					return;

				if ( ! target.is( ':visible' ) && target.html() == '' ) {
					bws_bkng_maps.add_single_map(
						target_id,
						{ center: { lat: target.attr( 'data-lat' ), lng: target.attr( 'data-lng' ) } }
					);
				}

				target.toggle();
			});
		}

		/**
		 * Adds the product to wishlist or removes it
		 */
		var wishlist_form = $( '.bkng_toggle_wishlist_form' );
		if ( wishlist_form.length ) {
			wishlist_form.find( '[name="bkng_toggle_wishlist"]' ).click( function( e ) {
				e.preventDefault();
				var in_wishlist = 'true' === e.target.dataset.inWishlist,
					post_id = wishlist_form.find( '[name="bkng_post_ID"]' ).val(),
					nonce = wishlist_form.find( '#bkng_nonce' ).val();

				e.target.disabled = true;
				$.ajax( {
					type: 'POST',
					url : bws_bkng.ajaxurl,
					data: {
						action: 'bkng_toggle_wishlist',
						in_wishlist: in_wishlist,
						post_id: post_id,
						bkng_nonce: nonce,
					},
					success: function( result ) {
						e.target.dataset.inWishlist = ! in_wishlist;
						e.target.innerText = result;
						e.target.disabled = false;
					},
					error : function( xhr, ajaxOptions, thrownError ) {
						alert( xhr.status );
						alert( thrownError );
					}
				} );
			} );
		}

		/**
		 * Add a product to the cart
		 */
		var add_to_cart_link = $( '.bws_bkng_add_to_cart_link' );
		if ( add_to_cart_link.length ) {

			var loader = $( '<span class="bas_bkng_icon bas_bkng_loader dashicons dashicons-image-rotate"></span>' );

			add_to_cart_link.click( function( event ) {

				event = event || window.event;
				event.preventDefault();

				var $this = $( this ),
					form  = $this.closest( 'form#bws_bkng_to_checkout_form' ),
					data  = bws_bkng_parse_query_string( $this.attr( 'href' ) ) || [],
					tick  = $( '<span class="bas_bkng_icon bws_bkng_added_product_confirm dashicons dashicons-yes"></span>' );

				if ( form.length ) {
					form.serializeArray().reduce( function( obj, item ) {
						switch( item.name ) {
							case 'bkng_from':
							case 'bkng_till':
								data[ item.name ] = Date.parse( item.value ) / 1000;
								break;
							case 'bkng_quantity':
								data[ item.name ] = parseInt( item.value );
								break;
							default:
								break;
						}
					}, {} );
				}

				if ( ! data )
					return false;

				data.action    = 'bkng_add_to_cart';
				data.is_single = bws_bkng.is_single;

				add_to_cart( data, $this, function( result ) {
					if ( typeof result.link != 'undefined' ) {
						tick.insertAfter($this);
						$this.replaceWith( result.link );
					} else if ( typeof result.errors != 'undefined' ) {
						$this.parent().find('.bws_bkng_message, .bws_bkng_error' ).remove();
						for ( var key in result.errors ) {
							$( '<div class="bws_bkng_error bws_bkng_' + key +'"></div>' ).text( result.errors[key][0] ).insertAfter( $this );
						}
					}
				});

			});
		}

		/*
		 * Add additional items to the cart on the single product page
		 */
		/*var add_extras_to_cart_link = $( '.bws_bkng_add_extras_to_cart_link' );
		if ( add_extras_to_cart_link.length ) {
			add_extras_to_cart_link.click( function( event ) {
				event = event || window.event;
				event.preventDefault();

				var $this = $( this ),
					data  = {
						action      : 'bkng_add_extras_to_cart',
						bkng_extras : []
					},
					temp = [];

				$this.next( '.dashicons' ).remove();
				$this.next( '.bws_bkng_error' ).remove();
				page.find( 'input, select' ).each( function() {
					var $this = $( this ),
						name  = $this.attr( 'name' ),
						val   = $this.val(),
						type  = $this.attr( 'type' );

					if ( ! name || ( ( type == 'radio' ||  type == 'checkbox' ) && ! $this.is( ':checked' ) ) )
						return;

					switch( name ) {
						case 'bkng_nonce':
							data.bkng_nonce = val;
							break;
						case 'bkng_quantity':
						case 'bkng_product':
							data[ name ] = parseInt( val );
							break;
						case 'bkng_from':
						case 'bkng_till':
							data[ name ] = Date.parse( val ) / 1000;;
							break;
						default:
							matches = name.match( /bkng_extras\[([0-9]+)\]\[(choose|quantity)\]/ );

							if ( ! matches )
								break;
							if ( typeof temp[ matches[1] ] == 'undefined' )
								temp[ matches[1] ] = { id: matches[1] };

							temp[ matches[1] ][ matches[2] ] = parseInt( val );

							break;
					}
				}, 0 );

				if ( typeof data.bkng_product == "undefined" )
					return false;

				data.bkng_extras = temp.reduce( function( arr, item ) {
					if ( typeof item.choose != 'undefined' ) {
						delete item.choose;
						arr.push( item );
					}
					return arr;
				}, [] );

				if ( ! data.bkng_extras.length ) {
					$( '<div class="bws_bkng_error bws_bkng_no_selected_extras"></div>' ).text( bws_bkng.choose_extras ).insertAfter( $this );
					return false;
				}

				add_to_cart( data, $this, function( result ) {
					if ( typeof result.errors != 'undefined' ) {
						for ( var key in result.errors ) {
							$( '<div class="bws_bkng_error bws_bkng_' + key +'"></div>' ).text( result.errors[key][0] ).insertAfter( $this );
						}
					} else if ( typeof result.message != 'undefined' ) {
						var main_product_link = $( '.bws_bkng_add_to_cart_link' );

						if ( main_product_link.length && typeof result.link != 'undefined' )
							main_product_link.replaceWith( result.link );

						$this.next( '.bws_bkng_message, .bws_bkng_error' ).remove();

						$( '<div class="bws_bkng_message"></div>' ).text( result.message ).insertAfter( $this );
						$( '<span class="dashicons dashicons-yes"></span>' ).insertAfter( $this );
					}
				});
			});
		}*/


		/**
		 * Tracking changes in the cart data and updating the storage
		 * when you click on the "Checkout" buttons and the "Back to products" links
		 * ---- The script needs some work ----
		 * ---- The development of the cart functionality has been suspended at the direction of A@ ------
		 */
		var cart_form = $( '.bws_bkng_cart_form' );

		if ( cart_form.length ) {

			function CartHandler() {
				var cart_inputs = cart_form.find( 'input[type="number"], input[type="text"]' ),
					delete_links = $( '.bws_bkng_delete_from_car_link' ),
					update_cart = false,
					/**
					 * Moving to a separate variable is useful if you need to add the ability to reset edited data
					 * to the initial state (at the time of entering the page)
					 */
					products = bws_bkng_cart.products;

				function count_rental_product_totals( id, from, till ) {
					var step = typeof products[ id ].rent_interval.step == 'undefined' ? false : products[ id ].rent_interval.step;

					subtotal = products[ id ].price;

					if ( step )
						subtotal *= Math.ceil( ( till - from ) / step );

					total = subtotal;

					return [ subtotal, total ];
				}

				function display_product_totals( id, totals ) {
					products[ id ].subtotal = totals[0];
					products[ id ].total    = totals[1];

					$( '.bws_bkng_product_' + id + '_subtotal' ).text( bws_bkng_number_format( totals[0] ) );
					$( '.bws_bkng_product_' + id + '_total' ).text( bws_bkng_number_format( totals[1] ) );
				}

				function update_cart_totals() {

					var cart_subtotal = cart_total = 0;

					$.each( products, function( id, data ) {
						if ( typeof data.delete == 'undefined' )
							cart_subtotal += data.total;
					});

					cart_total = cart_subtotal;

					$( '.bws_bkng_cart_subtotal' ).text( bws_bkng_number_format( cart_subtotal ) );
					$( '.bws_bkng_cart_total' ).text( bws_bkng_number_format( cart_total ) );
				}

				/**
				 * Processing form data editing
				 */
				cart_inputs.on( 'change', function() {
					cart_inputs.not( this ).attr( 'disabled', true );

					try {

						var id = this.name.match( /\d+/ )[0];
						update_cart  = true;

						if ( typeof products[ id ].delete != 'undefined' )
							return false;

						switch( this.type ) {
							case 'text':
								var is_from = this.name.match( /from/ ), from, till;

								if ( is_from ) {
									from = this.value;
									till = $( 'input[name="bkng_cart[' + id + '][rent_interval][till]"]' ).val();
								} else {
									from = $( 'input[name="bkng_cart[' + id + '][rent_interval][from]"]' ).val();
									till = this.value;
								}

								from = Date.parse( from ) / 1000;
								till = Date.parse( till ) / 1000;

								totals = count_rental_product_totals( id, from, till );
								display_product_totals( id, totals );

								/*
								 * Update all extras
								 * If it is a main product
								 */
								if ( ! products[ id ].linked_to ) {
									$.each( products, function( extra_id, data ) {
										if ( data.linked_to == id ) {
											totals = count_rental_product_totals( extra_id, from, till );
											display_product_totals( extra_id, totals );
										}
									});
								}
								break;
							case 'number':

								if( parseInt( this.value ) > parseInt( this.max ) )
									this.value = this.max;

								if ( parseInt( this.value ) < parseInt( this.min ) )
									this.value = this.min;

								subtotal = products[ id ].price * this.value;
								total    = subtotal;

								display_product_totals( id, [ subtotal, total ] );

								break;
							default:
								break;
						}

						update_cart_totals();
					} catch( e ) {
						update_cart = false;
					}

					cart_inputs.not( this ).attr( 'disabled', false );
				});

				/**
				 * Handling product deletion
				 */

				delete_links.click( function( event ) {
					event = event || window.event;
					event.preventDefault();

					var $this = $( this ),
						id    = $this.attr( 'data-product' ),
						data  = products[ id ];

					products[ id ].delete = true;
					$this.closest( 'tr' ).addClass( bws_bkng.hidden );

					/**
					 * Update all extras
					 * If it is a main product
					 */
					if ( ! products[ id ].linked_to ) {
						$.each( products, function( extra_id, data ) {
							if ( data.linked_to == id ) {
								products[ extra_id ].delete = true;
								delete_links.filter( function() {
									return $( this ).attr( 'data-product' ) == extra_id;
								}).closest( 'tr' ).addClass( bws_bkng.hidden );
							}
						});
					}

					update_cart_totals();
				});

				/**
				 * Saving data to session storage before redirecting to the Checkout page
				 * functional development is not finished
				 */
				$( '.bws_bkng_cart_button' ).click( function( event ) {
					if ( update_cart ) {
						event = event || window.event;

						if ( update_cart )
							event.preventDefault();
						/* nothing further has been written yet */
					}
				});
			}
			CartHandler();
		}

		/**
		 * Functionality for processing pre-order data on the product single page
		 */
		function PreOrderHandler( extras_wrap ) {

			function add_node( data ) {

				if ( typeof data.id == 'undefined' || nodes.hasOwnProperty( data.id ) )
					return false;

				data.id = bws_bkng_str_to_number( data.id );

				if ( ! data.id )
					return false;

				data.title     = data.title || "";
				data.price     = data.price ? bws_bkng_str_to_number( data.price, true ) : 0;
				data.quantity  = data.quantity ? bws_bkng_str_to_number( data.quantity ) : false;
				data.rent_step = data.rent_step ? bws_bkng_str_to_number( data.rent_step ) : 0;

				nodes[ data.id ] = data;

				return true;
			}

			function delete_node( id ) {

				id = bws_bkng_str_to_number( id );

				if ( ! id )
					return false;

				delete nodes[ id ];
			}

			function get_id( name ) {
				var matches = name.match( /bkng_extras\[([\d]+)\]/ );
				return matches[1] || false;
			}

			function change_rent_interval( datetimepicker ) {
				var from_date = datetimepicker[0].value,
					till_date = datetimepicker[1].value,
					from_timestamp = Date.parse( from_date ) / 1000,
					till_timestamp = Date.parse( till_date ) / 1000;

				if ( isNaN( from_timestamp ) || isNaN( till_timestamp ) || ( till_timestamp - from_timestamp ) < 0 )
					return;

				rent_interval.from.value = from_timestamp;
				rent_interval.till.value = till_timestamp;
			}

			function get_rent_interval_html( datetimepicker ) {
				return '<div class="bws_bkng_date_wrap bws_bkng_filter_date_from"><span class="bws_bkng_date_label">' + rent_interval.from.label + '</span>&nbsp;<span class="bws_bkng_date">' + datetimepicker[0].value + '</span></div><div class="bws_bkng_date_wrap bws_bkng_filter_date_till"><span class="bws_bkng_date_label">' + rent_interval.till.label + '</span>&nbsp;<span class="bws_bkng_date">' + datetimepicker[1].value + '</span></div>';
			}

			function get_extra_subtotal( id ) {

				var subtotal;

				if ( nodes[ id ]['rent_step'] && ( rent_interval.till.value - rent_interval.from.value ) )
					subtotal = nodes[ id ]['price'] * Math.ceil( ( rent_interval.till.value - rent_interval.from.value ) / nodes[ id ]['rent_step'] );
				else
					subtotal = nodes[ id ]['price'];

				if ( nodes[ id ]['quantity'] )
					subtotal *= nodes[ id ]['quantity'];

				return subtotal;
			}

			function count_product_subtotal() {

				if ( product_rent_step && ( rent_interval.till.value - rent_interval.from.value ) )
					product_subtotal = product_price * Math.ceil( ( rent_interval.till.value - rent_interval.from.value ) / product_rent_step );
				else
					product_subtotal = product_price;

				if ( product_quantity )
					product_subtotal *= product_quantity;
			}

			function count_extras_subtotal() {

				extras_subtotal = 0;

				for ( key in nodes )
					extras_subtotal += get_extra_subtotal( key );
			}

			function display_order_data() {
				/**
				 * Display product's desired quantity
				 */
				if ( product_quantity_wrap.length )
					product_quantity_wrap.text( bws_bkng_number_format( product_quantity, 0 ) );

				/**
				 * Display rent interval data
				 */
				if ( product_date_wrap.length && product_datetimepicker.length )
					product_date_wrap.html( get_rent_interval_html( product_datetimepicker ) );

				/**
				 * Change product subtotal and display it
				 */
				if ( product_subtotal_wrap.length )
					product_subtotal_wrap.text( bws_bkng_number_format( product_subtotal ) );

				if ( extras_wrap.length ) {

					/**
					 * Change and display extras subtotal
					 */
					count_extras_subtotal();

					/**
					 * Display extras subtotal
					 */
					if ( extras_subtotal_wrap.length )
						extras_subtotal_wrap.html( bws_bkng_number_format( extras_subtotal ) );

					/**
					 * Display list of selected extras
					 */
					var html        = '',
						hide_totals = always_hide_totals,
						iter        = 0,
						price, quantitty, currency, price_class;
					for ( key in nodes ) {
						currency    = '<span class="bws_bkng_currency">' + bws_bkng.currency + '</span>';
						price       = '<span class="bws_bkng_product_price">' + bws_bkng_number_format( get_extra_subtotal( key ) ) + '</span>';
						price       = 'left' == bws_bkng.currency_position ? currency + price : price + currency;
						price_class = nodes[ key ]['show_price'] ? '' : ' ' + bws_bkng.hidden;
						html        += '<div class="bws_bkng_selected_extra_' + key + '"><span class="bws_bkng_selected_extra_title">' + nodes[ key ]['title'] + '</span><span class="bws_bkng_price' + price_class + '">' + price + '</span></div>';

						/**
						 * Set the flag value in order to display/hide order totals
						 */
						if ( ! hide_totals && ! nodes[ key ]['show_price'] )
							hide_totals = true;

						iter ++;
					}

					if ( selected_extras_wrap.length )
						selected_extras_wrap.html( html );

					if ( always_hide_totals || hide_totals ) {
						extras_subtotal_wrap.parent().not( '.' + bws_bkng.hidden ).addClass( bws_bkng.hidden );
						order_total_wrap.parent().not( '.' + bws_bkng.hidden ).addClass( bws_bkng.hidden );
					} else if ( ! iter || ( ! always_hide_totals && ! hide_totals ) ) {
						extras_subtotal_wrap.parent().removeClass( bws_bkng.hidden );
						order_total_wrap.parent().removeClass( bws_bkng.hidden );
					}
				}

				/**
				 * Display order total
				 */
				if ( order_total_wrap.length )
					order_total_wrap.text( bws_bkng_number_format( product_subtotal + extras_subtotal ) );
			}

			/**
			 * Class variables
			 */
			var nodes         = {},
				rent_interval = bws_bkng.rent_interval,

				product_quantity_input  = $( '#bws_bkng_to_checkout_form input[name="bkng_quantity"]' ),
				product_rent_step_input = $( '#bws_bkng_to_checkout_form input[name="bkng_product_rent_interval_step"]' ),
				product_datetimepicker  = $( '#bws_bkng_to_checkout_form .bws_bkng_datepicker' ),
				product_price_wrap      = $( '.bws_bkng_order_product_price .bws_bkng_product_price' ),
				product_quantity_wrap   = $( '.bws_bkng_order_product_quantity' ),
				product_date_wrap       = $( '.bws_bkng_product_rent_interval' ),
				product_subtotal_wrap   = $( '.bws_bkng_product_subtotal' ),
				extras_subtotal_wrap    = $( '.bws_bkng_extras_subtotal' ),
				order_total_wrap        = $( '.bws_bkng_order_total' ),
				extras_wrap             = $( '#bws_bkng_extras' ),
				product_price           = product_price_wrap.length      ? bws_bkng_str_to_number( product_price_wrap[0].innerText, true ) : 0,
				product_quantity        = product_quantity_input.length  ? bws_bkng_str_to_number( product_quantity_input.val() ) : 1,
				product_rent_step       = product_rent_step_input.length ? bws_bkng_str_to_number( product_rent_step_input.val() ) : 0,
				product_subtotal        = product_subtotal_wrap.length   ? bws_bkng_str_to_number( product_subtotal_wrap[0].innerText, true ) : 0,
				extras_subtotal         = 0,
				always_hide_totals      = product_price_wrap.parent().hasClass( bws_bkng.hidden );

			product_quantity_input.on( 'change', function() {
				var $this = $( this ),
					max   = parseInt( $this.attr( 'max' ) ),
					value = parseInt( this.value );

				product_quantity = bws_bkng_str_to_number( $this.val() );

				/* For browsers that don't support input:number */
				if ( value > max )
					value = max;
				else if ( value < 1 || isNaN( value ) )
					value = 1;

				$this.val( value );

				product_quantity = bws_bkng_str_to_number( value );

				count_product_subtotal();
				display_order_data();
			});

			product_datetimepicker.on( 'change', function() {
				change_rent_interval( product_datetimepicker );
				count_product_subtotal();
				display_order_data();
			});

			if ( ! extras_wrap.length )
				return;

			var extras_checkbox         = extras_wrap.find( 'input[type="checkbox"]' ),
				extras_quantity_input   = extras_wrap.find( 'input[type="number"]' ),
				selected_extras_wrap    = $( '.bws_bkng_selected_extras' );

			extras_checkbox.on( 'change', function() {

				var $this      = $( this ),
					product_id = get_id( $this.attr( 'name' ) ),
					current_wrap, equal_input;

				if ( ! product_id )
					return;

				equal_input = extras_quantity_input.filter( '[name="bkng_extras[' + product_id + '][quantity]"]' );

				if ( ! $this.is( ':checked' ) ) {

					delete_node( product_id );

				} else {
					current_wrap = $this.closest( '.bws_bkng_extra' ).find( '.bws_bkng_product_title' );

					if ( ! nodes.hasOwnProperty( product_id ) ) {
						add_node({
							id: product_id,
							title: current_wrap.length ? current_wrap.text() : "",
							quantity: equal_input.length ? equal_input.val() : false,
							price: $this.attr( 'data-price' ),
							rent_step: $this.attr( 'data-rent-step' ),
							show_price: parseInt( $this.attr( 'data-show-price' ) )
						});
					}
				}

				if ( equal_input.length )
					equal_input.val( $this.is( ':checked' ) ? 1 : 0 );

				display_order_data();
			});

			extras_quantity_input.on( 'keyup change click', function() {

				var $this      = $( this ),
					product_id = get_id( $this.attr( 'name' ) ),
					max        = parseInt( $this.attr( 'max' ) ),
					quantity   = parseInt( $this.val() ),
					current_wrap, equal_input;

				/* For browsers that don't support input:number */
				if ( quantity > max )
					quantity = max;
				else if ( quantity < 0 || isNaN( quantity ) )
					quantity = 0;

				$this.val( quantity );

				if ( ! product_id )
					return;

				equal_input = extras_checkbox.filter( '[name="bkng_extras[' + product_id + '][choose]"]' );

				if( ! quantity ) {
					delete_node( product_id );
					equal_input.attr( "checked", false );
				} else if ( nodes.hasOwnProperty( product_id ) ) {
					nodes[ product_id ]['quantity'] = quantity;
				} else {

					current_wrap = $this.closest( '.bws_bkng_extra' ).find( '.bws_bkng_product_title' );

					add_node({
						id: product_id,
						title: current_wrap.length ? current_wrap.text() : "",
						quantity: quantity,
						price: equal_input.attr( 'data-price' ),
						rent_step: equal_input.attr( 'data-rent-step' ),
						show_price: equal_input.attr( 'data-show-price' )
					});

					equal_input.attr( "checked", true );
				}

				display_order_data();
			});

			/**
			 * Display rent interval in the Preoerder widget
			 * This hack is needed in order to avoid the error in determining
			 * the rent interval when using the PHP and JS functionality
			 */
			if ( product_date_wrap.length && product_datetimepicker.length  )
				product_date_wrap.html( get_rent_interval_html( product_datetimepicker ) );
		}
		PreOrderHandler();


		$( 'a[href="#bws_bkng_terms_and_conditions"]' ).click( function( event ) {
			event = event || window.event;
			event.preventDefault();

			$( $( this ).attr( 'href' ) ).toggle();
		});
	});
})( jQuery );
