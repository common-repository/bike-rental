/**
 * Handles the extras tree on the products edit pages
 * @type {class}
 * @constructor
 * @param    {string|object}    selector  jQuery-selector
 * @return   {void}
 */
function BwsBkngTree( selector ) {
		/**
		 * jQuery-selector, tree wrap
		 * @access private
		 * @var    {string}
		 */
		this._selector = selector;

		/**
		 * jQuery-selector, messages wrap
		 * @access private
		 * @var    {string}
		 */
		this._message_class = this._selector.replace( /[^\w\d\-\_]/g, "" ) + '_message';

		/**
		 * jQuery-object, the tree
		 * @access private
		 * @var    {object}
		 */
		this._tree = null;

		/**
		 * Contains the offset of the scrolling window relative to the beginning of the html document,
		 * used to determine in which direction the vertical scrolling occurs
		 * @see    this.prototype._load_next_page
		 * @access private
		 * @var    {int}
		 */
		this._scroll_buffer = 0;

		/**
		 * @uses To disable the window "scroll" event handler until the next part of the tree will be loaded
		 * @see    this.prototype._load_next_page
		 * @access private
		 * @var    {boolean}
		 */
		this._scroll_event_disabled = false;

		/**
		 * Temporary storage, contains nodes data that were marked(unmarked) by the user before further saving to the database
		 * @see    this.prototype._render_storage, this.prototype._get_items
		 * @access private
		 * @var    {int}
		 */
		this._storage = {};

		/**
		 * The timer identifier, according to which the tree data are automatically saved
		 * @see    this.prototype.init
		 * @access private
		 * @var    {int}
		 */
		this._timer = 0;

		this.init();
}

/**
 * The BwsBkngTree class prototype
 * @var    {object}
 */
BwsBkngTree.prototype = {
	/**
	 * jQuery window-object
	 * @const
	 * @access public
	 * @var {object}
	 */
	WIN: jQuery( window ),

	/**
	 * The currently edited product(or its variation) ID
	 * @const
	 * @access public
	 * @var {int}
	 */
	POST_ID: parseInt( jQuery( 'input[name="bkng_edited_variation"]' ).val() ) || parseInt( jQuery( 'input[name="post_ID"]' ).val() ),

	/**
	 * The number of tree items per page
	 * @uses for thepagination
	 * @see  this.init
	 * @const
	 * @access public
	 * @arg  {int}   The number of elements from the calculation, to make the list fit about 3 screen heights.
	 */
	POSTS_PER_PAGE: 0,

	/**
	 * Initializes the main tree functionality and registers the required event handlers
	 * @access public
	 * @type   {function}
	 * @param  {void}
	 * @return {void}
	 */
	init: function() {

		this._tree = jQuery( this._selector );

		/*
		 * The number of tree items
		 * is calculated in such a way that the list occupies a size equal to 3 screen heights.
		 */
		if ( ! this.POSTS_PER_PAGE )
			this.POSTS_PER_PAGE = this.WIN.height() / 7;

		/**
		 * Use scope in order to not lose the context
		 */
		var instance = this;

		instance._tree.fancytree({
			source: instance._get_items(), /* load categories */
			checkbox: true,
			selectMode: 3,
			lazyLoad: function( event, ui ) {
				var selected = ui.node.isSelected() ? true : ( ui.node.partsel ? 'partsel' : false );
				ui.result = instance._get_items( ui.node.data.cat, 1, ui.node.data.total_posts, 'bottom', selected );
			},
			click: function( event, ui ) {
				if ( 'checkbox' == ui.targetType ) {
					if ( instance._timer )
						clearTimeout( instance._timer );

					instance._render_storage( ui.node );

					/*
					 * The data will be automatically saved in 3 seconds after the editing is finished
					 */
					instance._timer = setTimeout( function() {
						if ( Object.keys( instance._storage ).length )
							instance._save();
					}, 3000 );

				}
			},
			blurTree: function( event, ui ) {
				instance._tree.fancytree( "getRootNode" ).visit( function( node ) {
					node.setExpanded( false );
				});
			}
		});

		/**
		 * Add 'scroll'-event handler for lazy page loading
		 * @see this._load_next_page()
		 */
		instance.WIN.scroll( function() {
			instance._load_next_page();
		});

		/**
		 * Hide service messages
		 * @see this._show_message()
		 */
		jQuery( document ).on( 'click', '.' + instance._message_class + ' .notice-dismiss', function() {
			jQuery( this ).parent( '.' + instance._message_class ).remove();
		});

		/**
		 * Checks the storage and save tree data before post data saving.
		 */
		jQuery( '#publish[name="save"]' ).click( function( event ) {
			if ( Object.keys( instance._storage ).length ) {
				event = event || window.event;
				event.preventDefault();
				instance._save( this );
			}
		});
	},

	/**
	 * Saves the tree via AJAX-call
	 * @access private
	 * @type   {function}
	 * @param  {object}     save_button      The jQuery-object of the "Update" post button
	 * @return {void}
	 */
	_save: function( save_button ) {
		var instance = this;
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: {
				action           : 'bkng_save_tree',
				bkng_nonce       : bkng_fancytree_vars.nonce,
				bkng_tree_data   : instance._storage,
				bkng_tree_type   : this._selector.replace( /\.bkng_([\w\d]+)_tree/, "$1" ),
				bkng_post_id     : this.POST_ID
			},
			success: function( result ) {
				try {
					/**
					 * Clear the storage after the data saved
					 */
					instance._storage = {};

					if ( save_button )
						jQuery( save_button ).trigger( 'click' );
					else
						instance._show_message( jQuery.parseJSON( result ) );

				} catch( e ) {
					instance._show_message({
						error: bkng_fancytree_vars.error + '<br>' + result
					});
				}
			},
			error: function( options, event, thrownError ) {
				instance._show_message({
					error: bkng_fancytree_vars.error + ':<br>' + thrownError
				});
			}
		});
	},

	/**
	 * Displays the action results
	 * @see      this._save
	 * @access   private
	 * @type     {function}
	 * @param    {object}    result
	 * @return   {void}
	 */
	_show_message: function( result ) {
		/* remove old messages */
		jQuery( '.' + this._message_class ).remove();
		/* add new messages */
		for ( key in result )
			jQuery( this._tree ).prepend( '<div class="' + key + ' ' + this._message_class + ' notice is-dismissible inline"><p>' + result[ key ] + '</p><button type="button" class="notice-dismiss"></button></div>' );
	},

	/**
	 * Fetch the tree nodes
	 * @see      this.init, this._load_next_page
	 * @access   private
	 * @type     {function}
	 * @param    {string}          category        The currently viewed products category slug.
	 * @param    {int}             next_page       The next page number.
	 * @param    {int}             total_posts     Total number of products in the category.
	 * @param    {string}          position        In which direction did the scrolling of the browser window occur
	 *                                             at the moment when the function was called.
	 * @param    {boolean|string}  selected        boolean - whether the whole category is marked, string("partsel") - if the category is partly selected.
	 * @return   {object}                          XMLHttpRequest object
	 */
	_get_items: function( category, next_page, total_posts, position, selected ) {
		var instance = this,
			category    = category || '',
			next_page   = next_page || 1,
			position    = position || 'bottom',
			total_posts = total_posts || 0,
			selected    = typeof selected == "undefined" ? 'partsel' : selected;

		return jQuery.ajax({
			url      : ajaxurl,
			type     : "POST",
			dataType : "json",
			data: {
				action           : 'bkng_get_tree_items',
				bkng_category    : category,
				bkng_total_posts : total_posts,
				bkng_per_page    : instance.POSTS_PER_PAGE,
				bkng_next_page   : next_page,
				bkng_nonce       : bkng_fancytree_vars.nonce,
				bkng_position    : position,
				bkng_selected    : selected,
				bkng_tree_type   : this._selector.replace( /\.bkng_([\w\d]+)_tree/, "$1" ),
				bkng_post_id     : this.POST_ID
			},
			success: function( result ) {
				//
			},
			error : function ( options, event, thrownError ) {
				instance._show_message({
					error: bkng_fancytree_vars.error + ':<br>' + thrownError
				});
			}
		});
	},

	/**
	 * Fetch the node for the next/previous page loading
	 * @see      this._load_next_page
	 * @access   private
	 * @type     {function}
	 * @param    {string}          cat          The currently viewed products category slug.
	 * @param    {string}          position     In which direction did the scrolling of the browser window occur at the moment when the function was called.
	 * @param    {int}  selected   page         The page number, which will need to be loaded, after the reaching this node at the moment of scrolling.
	 * @return   {object}                       The node data
	 */
	_get_anchor: function( cat, position, page ) {
		return {
			title          : bkng_fancytree_vars.loader.title,
			key            : bkng_fancytree_vars.loader.key,
			id             : bkng_fancytree_vars.loader.key,
			icon           : false,
			cat            : cat,
			next_page      : page,
			statusNodeType : 'paging',
			position       : position,
			selected       : true,
			extraClasses   : bkng_fancytree_vars.loader.key + '_' + position,
			hideCheckbox   : true,
			unselectable   : true
		};
	},

	/**
	 * Prepare storage data before sending them via AJAX
	 * @see      this.init
	 * @access   private
	 * @type     {function}
	 * @param    {object}    node   The node that was marked/unmarked by the user
	 * @return   {void}
	 */
	_render_storage: function( node ) {

		var node_selected = ! node.isSelected(),
			parent;

		/* Clik on the products category */
		if ( node.isFolder() ) {
			this._storage[ node.key ] = {
				folder   : true,
				cat      : node.key,
				id       : node.data.id,
				selected : node_selected
			};

			node.data.selected_count = node_selected ? node.data.total_posts : 0;

			/**
			 * Remove all products of the given category from the storage
			 */
			this._remove_products_from_storage( node.data.cat );
		} else {
			parent = node.getParent();

			if ( node_selected ) {
				parent.data.selected_count ++;

				/* If all products from the category are marked */
				if ( parent.data.selected_count == parent.data.total_posts ) {
					this._storage[ parent.key ] = {
						folder   : true,
						cat      : parent.key,
						id       : parent.data.id,
						selected : true
					};

					this._remove_products_from_storage( parent.key );
				} else {

					this._storage[ node.key ] = {
						cat      : parent.data.cat,
						id       : node.key,
						selected : node_selected
					};

					if ( typeof this._storage[ node.data.cat ] != 'undefined' )
						delete this._storage[ node.data.cat ];
				}
			} else {
				parent.data.selected_count --;
				/**
				 * If the whole category early was binded to the managed product
				 */
				if( parent.isSelected() || ( ! parent.data.selected_count && typeof this._storage[ parent.key ] == 'undefined' ) ) {
					this._storage[ parent.key ] = {
						folder   : true,
						cat      : parent.key,
						id       : parent.data.id,
						selected : false
					};

					this._remove_products_from_storage( parent.key );
				}
				if ( parent.data.selected_count ) {
					this._storage[ node.key ] = {
						cat      : parent.data.cat,
						id       : node.key,
						selected : node_selected
					};
				}
			}
		}
	},

	/**
	 * Removes nodes from the storage
	 * @see      this._render_storage
	 * @access   private
	 * @type     {function}
	 * @param    {string}        searched_key
	 * @return   {void}
	 */
	_remove_products_from_storage: function( searched_key ) {
		for ( var key in this._storage ) {
			if ( key != searched_key && this._storage[ key ].cat == searched_key )
				delete this._storage[ key ];
		}
	},

	/**
	 * Loads the next/previous page during the scroll of the browser window.
	 * @see      this.init
	 * @access   private
	 * @type     {function}
	 * @param    {void}
	 * @return   {void}
	 */
	_load_next_page: function() {

		if ( this._scroll_event_disabled )
			return false;

		var instance      = this,
			scroll_top    = this.WIN.scrollTop(),
			is_downscroll = scroll_top > this._scroll_buffer,
			position      = is_downscroll ? "bottom" : "top",
			opposite      = is_downscroll ? "top" : "bottom",
			res           = [],
			first_value, second_value, prev_page, next_page, anchor, loaded_page, node, parent_node, scroll_offset, opposite, bottom_anchor;

		jQuery.each( this._tree.find( '.bkng_more_anchor_' + position ), function() {
			anchor = jQuery( this );

			if ( is_downscroll ) {
				first_value  = scroll_top + instance.WIN.height();
				second_value = anchor.offset().top;
			} else {
				first_value  = anchor.offset().top + anchor.outerHeight();
				second_value = scroll_top;
			}

			if ( first_value >= second_value ) {
				instance._scroll_event_disabled = true;
				node   = jQuery.ui.fancytree.getNode( anchor );
				parent = node.getParent();

				if ( ! parent.expanded ) {
					instance._scroll_event_disabled = false;
					return;
				}
				/* currently founded anchor data */
				loaded_page = node.data.next_page;
				node.replaceWith( instance._get_items(
					parent.data.cat,
					loaded_page,
					parent.data.total_posts,
					position,
					( parent.isSelected() ? true : ( parent.partsel ? 'partsel' : false ) )
				)).done( function() {

					parent.render();

					if ( is_downscroll ) {
						prev_page = loaded_page - 2;
						next_page = loaded_page + 1;
					} else {
						prev_page = loaded_page - 1;
						next_page = loaded_page + 2;
					}

					parent.visit( function( child ) {
						if (
							/* previous page posts */
							( child.data.current_page && Math.abs( child.data.current_page - loaded_page ) > 1 ) ||
							/* previous page anchor */
							( child.data.position && 'top' == child.data.position && child.data.next_page != prev_page ) ||
							/* next page anchor */
							( child.data.position && 'bottom' == child.data.position && child.data.next_page != next_page )
						)
							res.push( child );
					});

					if ( res.length ) {
						res.forEach( function( node ) {
							node.remove();
						});
					}

					/* When scrolling down and loading a second or more page */
					if ( is_downscroll && loaded_page > 2 ) {
						parent.addNode( instance._get_anchor( node.data.cat, 'top', loaded_page - 2 ), 'firstChild' );
					/* When scrolling up */
					} else if ( ! is_downscroll && Math.ceil( parent.data.total_posts / instance.POSTS_PER_PAGE ) - loaded_page > 2 ) {
						parent.addNode( instance._get_anchor( node.data.cat, 'bottom', loaded_page + 2 ) );
					}

					if ( is_downscroll ) {
						bottom_anchor = instance._tree.find( '.bkng_scroll_to_top_' + node.data.cat + '_' + loaded_page );
						if ( bottom_anchor.length )
							scroll_offset = bottom_anchor.offset().top - 50;
					} else {
						scroll_offset = instance.WIN.height() + instance.WIN.scrollTop();
					}

					instance.WIN.scrollTop( scroll_offset );
					instance._scroll_event_disabled = false;

					parent.render();
				});
			}
		});

		/* change bufffer value */
		this._scroll_buffer = scroll_top;
	}
}

