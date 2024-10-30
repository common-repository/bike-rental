(function( $ ) {
	var page = $( document );
	page.ready( function() {
		/**
		 * Show/hide the return locations list in the products search form
		 */
		$( '.bkrntl-return-different-location' ).on( 'change', function() {
			var $this = $( this ),
				return_location_list = $this.parent().parent().next( 'div' );
			if ( $this.is( ':checked' ) ){
				return_location_list.show();
				$this.parents( '.bws_bkng_search_products_item' ).prev( 'div' ).addClass( 'display_return' );
			} else {
				return_location_list.hide();
				$this.parents( '.bws_bkng_search_products_item' ).prev( 'div' ).removeClass( 'display_return' );
			}
		}).trigger( 'change' );

		/**
		 * Change the size of search form's thumbnail in slider
		 */
		var search_form_image = $( '.bws_bkng_form_img' );
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
	});

	/**
	 * Change the default behaviour of the Products Search Form
	 */
	var error_code;
	BwsBkngHooks.add_filter( 'products_search_form_data', function( data ) {

		error_code = false;

		if ( typeof data.bkng_pickup_location != 'undefined' ) {
			data.pickup_location = data.bkng_pickup_location;
			delete data.bkng_pickup_location;
		}

		if ( typeof data.bkng_return_different_location == 'undefined' ) {
			return data;
		} else if ( typeof data.bkng_return_location == 'undefined' ) {
			error_code = 'empty_return_location';
		} else {
			data.return_location = data.bkng_return_location;
			delete data.bkng_return_location;
		}

		delete data.bkng_return_different_location;


		return data;

	}).add_filter( 'products_search_submit_allowed', function( submit_allowed ) {

		return error_code.length ? false : submit_allowed;

	}).add_filter( 'products_search_error', function( error ) {

		return bkrntl[ error_code ];

	});

})(jQuery);