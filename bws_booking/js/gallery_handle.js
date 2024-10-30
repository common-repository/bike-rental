/**
 * Initializes functionality for editing plugin galleries and featured images for plugin taxonomies,
 * as well as displaying everything in the frontend
 * @type {class}
 * @constructor
 * @param    {object}    gallery  jQuery-object
 * @return   {void}
 */
function BwsBkngGallery( gallery ) {
	this._gallery = gallery;

	if ( bws_bkng_gallery.is_admin ) {
		this._is_single = !! gallery.attr( 'data-single' );
		this.admin_init();
	} else {
		this.init();
	}
}

/**
 * We add the necessary methods and properties through the prototype in order to prevent duplication of methods
 * when creating multiple instances of the BwsBkngGallery class
 * @var    {object}
 */
BwsBkngGallery.prototype = {
	/**
	 * jQuery document-object used when working with the global window object through the jQuery library,
	 * introduced to optimize the script
	 * @const
	 * @access public
	 * @var {object}
	 */
	PAGE: jQuery( document ),

	/**
	 * Initialization of the main gallery functionality for the admin area
	 * @access public
	 * @type   {function}
	 * @param  {void}
	 * @return {void}
	 */
	admin_init: function( ) {
		var instance = this;

		this._id_list    = this._gallery.parent().find( '.bkng_gallery_list_id' );
		this._add_button = this._gallery.parent().find( '.bkng_add_image' );
		this._frame      = null;

		/**
		 * Open the image loader modal window
		 */
		this._add_button.click( function( event ) {
			event = event || window.event;
			event.preventDefault();
			var $this = jQuery( this );
			if ( ! instance._frame ) {
				instance._frame = wp.media({
					multiple: ! instance._is_single,
					title: $this.data( 'title' ),
					button: {
						text: $this.data( 'button-title' )
					}
				});
				instance._frame.on( 'select', function() {
					instance.add_image();
				});
			}
			instance._frame.open();
		});

		/*
		 * remove images from gallery
		 */
		this.PAGE.on( 'click', '.bkng_delete_image', function( event ) {
			event = event || window.event;
			event.preventDefault();
			jQuery( this ).closest( 'li.bkng_post_image' ).remove();
			instance.update_id_list();
		});

		/*
		 * using image sorter
		 */
		if ( ! this._is_single ) {
			this._gallery.sortable({
				items: 'li.bkng_post_image',
				cursor: 'move',
				scrollSensitivity: 40,
				forcePlaceholderSize: true,
				forceHelperSize: false,
				helper: 'clone',
				opacity: 0.8,
				placeholder: 'bkng-sortable-placeholder',
				update: function() {
					instance.update_id_list();
				}
			});
		}
		this.PAGE.on( 'bkng_reset', function() {
			instance._gallery.find( '.bkng_post_image' ).remove();
			instance._id_list.val( '' );
		});
	},

	/**
	 * Initializes the main gallery functionality in the frontend
	 * @access public
	 * @type   {function}
	 * @param  {void}
	 * @return {void}
	 */
	init: function() {
		if ( typeof jQuery.fn.fancybox != 'undefined' );
			jQuery( 'a[data-fancybox^="bkng_gallery"]' ).fancybox({loop : true, infobar : true});
	},

	/**
	 * Adds images to gallery
	 * @access public
	 * @type   {function}
	 * @param  {void}
	 * @return {void}
	 */
	add_image: function() {
		var instance  = this,
			selection = this._frame.state().get( 'selection' ),
			image_ids = this._id_list.val().split( ',' ).filter( function( id ) {
				return !!id;
			}).map( function( id ) {
				return parseInt( id );
			});

		selection.map( function( image ) {
			image = image.toJSON();

			if ( image.id && jQuery.inArray( image.id, image_ids ) == -1 ) {
				var src = image.sizes && image.sizes.thumbnail ? image.sizes.thumbnail.url : image.url;

				if ( instance._is_single )
					instance._gallery.find( '.bkng_post_image' ).remove();

				instance._gallery.append( '<li class="bkng_post_image" data-image-id="' + image.id + '"><img src="' + src + '" /><button type="button" class="bkng_delete_image dashicons dashicons-trash" style="font-family: dashicons;"></button></li>' );
			}
		});

		this.update_id_list();
	},

	/**
	 * Updates a hidden field with IDs of attached images
	 * @access public
	 * @type   {function}
	 * @param  {void}
	 * @return {void}
	 */
	update_id_list: function() {
		var image_ids = [], image_id, new_value, title;

		this._gallery.find( 'li.bkng_post_image' ).each( function() {
			image_id = jQuery( this ).attr( 'data-image-id' );
			image_ids.push( image_id );
		});

		new_value = image_ids.join( ',' );

		this._id_list.val( new_value );

		/* change the caption of the button that opens the image selection window */
		if ( this._is_single )
			this._add_button.text( new_value ? bws_bkng_gallery.update_featured : bws_bkng_gallery.set_featured );
	}
}