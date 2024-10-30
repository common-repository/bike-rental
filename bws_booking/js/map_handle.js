/**
 * Contains the necessary set of functionality for working with the Google Maps API
 * @class
 */
function BwsMapHandler() {

	/**
	 * Stores default settings for the initial drawing of maps,
	 * if any parameter is not specified or is not in the correct format
	 * @type {Object}
	 * @access private
	 */
	var map_defaults = {
		zoom: 15,
		center: {
			lat: parseFloat( bws_bkng_map.default_lat ),
			lng: parseFloat( bws_bkng_map.default_lng )
		}
	}

	/**
	 * Contains a pointer to an object of the class in the context of which it is used.
	 * Is required for access to their own items on the external interface
	 * @type {Object}
	 * @access private
	 */
	var self = this;

	/**
	 * Clears coordinates before being processed by the Google Maps API
	 * @type    {function}
	 * @acccess private
	 * @param   {string}  param    Property from the options object that needs to be processed
	 * @param   {Object}  options  List of parameters used when generating maps
	 * @return  {Object}           Cleared coordinates in the format { lat: {float}, lng: {float} }
	 */
	function esc_coordinates( param, options ) {
		if (
			typeof options[ param ] != 'object' ||
			options[ param ] === null
		)
			return map_defaults.center;

		var coors = [ 'lat', 'lng' ];

		for ( var i = 0; i < coors.length; i ++ ) {
			if (
				typeof options[ param ][ coors[ i ] ] === 'undefined' ||
				options[ param ][ coors[ i ] ] == ''
			)
				options[ param ][ coors[ i ] ] = map_defaults.center[ coors[ i ] ];
			else
				options[ param ][ coors[ i ] ] = parseFloat( options[ param ][ coors[ i ] ] );
		}

		return options[ param ];
	}

	/**
	 * Clears the zoom parameter before generating the map
	 * @type    {function}
	 * @acccess private
	 * @param   {Object} options  List of parameters used when generating maps
	 * @return  {int}             Restored Zoom
	 */
	function esc_zoom( options ) {
		return typeof options.zoom === 'undefined' ? 15 : Math.abs( parseInt( options.zoom ) );
	}

	/**
	 * Clears the line entered by the user in the address input field before generating the map
	 * @type    {function}
	 * @access private
	 * @param   {Object}  options   List of parameters used when generating maps
	 * @return  {string}            Cleared address
	 */
	function esc_address( options ) {
		return typeof options.address === 'undefined' ? '' : options.address;
	}

	function scroll_to_center( map_id, coordinates, move_marker ) {
		/* Scroll the map to the necessary location */
		self.map[ map_id ].setCenter( coordinates );
		/* Move the marker to the map center */
		if ( move_marker )
			self.marker[ map_id ][0].setPosition( coordinates );
	}

	/**
	 * Stores objects of initialized maps for the possibility of their further processing
	 * @see    https://developers.google.com/maps/documentation/javascript/examples/marker-remove
	 * @type {array}
	 * @access public
	 */
	this.map = [];

	/**
	 * Stores objects of initialized maps for the possibility of their further processing
	 * @see    https://developers.google.com/maps/documentation/javascript/examples/marker-remove
	 * @type {array}
	 * @access public
	 */
	this.marker = [];

	/**
	 * Generates a map in the specified wrapper with one marker
	 * Used on the post edit page
	 * @type    {function}
	 * @access public
	 * @param   {string}  map_id          The value of the HTML attribute "id" of the element where the map needs to be generated
	 * @param   {Object}  options          List of parameters used when generating a map
	 * @param   {string}  callback         Callback function called when clicking on the map or dragging the marker
	 * @return  {void}
	 */
	this.add_single_map = function( map_id, options, callback ) {

		if ( ! map_id )
			return;

		callback = callback || '';
		/* Parse options */
		options.zoom   = esc_zoom( options );
		options.center = esc_coordinates( 'center', options );

		/* Add map */
		self.map[ map_id ] = new google.maps.Map( document.getElementById( map_id ), options );

		/* Add marker to the map */
		self.marker[ map_id ]    = {};
		self.marker[ map_id ][0] = new google.maps.Marker({
			position : options.center,
			draggable: !!bws_bkng_map.is_admin,
			animation: google.maps.Animation.DROP,
			map      : self.map[ map_id ]
		});

		/*
		 * Needs for drawing multiple maps on single page
		 */
		setTimeout( function() {
			google.maps.event.trigger( self.map[ map_id ], 'resize');
			self.map[ map_id ].setCenter( options.center );
		}, 500 );

		if ( !!bws_bkng_map.is_admin ) {
			/* Change marker's position by click on the map */
			google.maps.event.addListener( self.map[ map_id ], 'click', function( event ) {
				self.find_on_single_map( 'location', map_id, { location: self.parse_coordinates( event.latLng ) }, callback );
			});

			/* Get new marker coordinates by drop it on the map */
			google.maps.event.addListener( self.marker[ map_id ][0], 'dragend', function( event ) {
				self.find_on_single_map( 'location', map_id, { location: self.parse_coordinates( event.latLng ) }, callback );
			});

			jQuery( document ).on( 'bkng_reset', function() {
				scroll_to_center( map_id, map_defaults.center, true );
			});
		}
	};

	/**
	 * Generates a map in the specified wrapper and draws the required number of markers there
	 * @type    {function}
	 * @acccess public
	 * @param   {string}  map_id          The value of the HTML attribute "id" of the element where the map needs to be generated
	 * @param   {Object}  options         List of parameters used when generating a map
	 * @param   {array}   markers         List (array of objects) data for drawing markers
	 * @return  {void}
	 */
	this.add_multi_map = function( map_id, options, markers ) {
		if ( ! map_id )
			return;

		/* Parse options */
		options.zoom   = esc_zoom( options );
		options.center = esc_coordinates( 'center', options );

		/* Add map */
		self.map[ map_id ] = new google.maps.Map( document.getElementById( map_id ), options );

		/*  */
		var bounds = new google.maps.LatLngBounds();

		/* Add markers to the map */
		self.marker[ map_id ] = [];

		for ( var key in markers ) {
			self.marker[ map_id ][ key ] = new google.maps.Marker({
				position: { lat: markers[key]['lat'], lng: markers[key]['lng'] },
				map: self.map[ map_id ],
				bkng_id: markers[key]['id'],
				bkng_title: markers[key]['title']
			});

			bounds.extend( self.marker[ map_id ][ key ].getPosition() );
		}

		/* Set map center according to the markers positions */
		self.map[ map_id ].setCenter( bounds.getCenter() );
		self.map[ map_id ].fitBounds( bounds );
	};

	/**
	 * Searches for a location on the map
	 * Used on the post edit page
	 * @type    {function}
	 * @access public
	 * @param   {string}  by               What is the basis for looking for a location. Can be set as "adddress" or "location"
	 * @param   {string}  map_id          The value of the HTML attribute "id" of the element where the map needs to be generated
	 * @param   {Object}  options          List of parameters used when generating a map
	 * @param   {string}  callback    Callback function to be called after the search operation completes
	 * @return  {void}
	 */
	this.find_on_single_map = function( by, map_id, options, callback ) {

		if ( ! map_id )
			return;

		callback = callback || '';

		/* Parse options */
		options[ by ] = 'address' == by ? esc_address( options ) : esc_coordinates( 'location', options );

		var geocoder = new google.maps.Geocoder(), coordinates = {};

		/* Search the position according to the recieved options */
		geocoder.geocode( options, function( result, status ) {

			if ( typeof result[0] == 'undefined' || typeof result[0].geometry == 'undefined' || status != google.maps.GeocoderStatus.OK ) {
				result[0] = { error: bws_bkng_map.find_error + "\n" + status };
			} else {
				coordinates = self.parse_coordinates( result[0].geometry.location );
				if ( typeof self.map[ map_id ] === 'undefined' )
					self.add_single_map( map_id, { center: coordinates } );
				else
					scroll_to_center( map_id, coordinates, true );
			}

			if ( typeof callback == "function" )
				callback( result[0] );
		});
	};

	/**
	 * Find markers that are within a specified radius from a specified point
	 * @type    {function}
	 * @access public
	 * @param   {string}  map_id    ID of the map, among the markers of which you need to find the required
	 * @param   {Object}  center    Center coordinates
	 * @param   {int}     radius    Maximum distance from the center of the search
	 * @return  {array}   found     List of IDs (from the database) found markers
	 */
	this.find_in_radius = function( map_id, center, radius ) {

		if ( ! map_id )
			return;

		radius = parseInt( radius );

		var found = [], distance;

		for ( var key in self.marker[ map_id ] ) {

			distance = parseInt( google.maps.geometry.spherical.computeDistanceBetween(
					center,
					self.marker[ map_id ][ key ].position
				)
			);

			if ( distance < radius )
				found[ key ] = self.marker[ map_id ][ key ][ 'bkng_id' ];
		}

		/* remove empty values */
		found = found.filter( function( item ) {
			return !! parseInt( item );
		});

		if ( !! found.length )
			scroll_to_center( map_id, center, false );

		return found;
	};

	/**
	 * Converts the presentation format of coordinates
	 * @type    {function}
	 * @access public
	 * @param   {Object}  location   google.maps.LatLng object
	 * @return  {Object}             Coordinates in the format { lat: {float}, lng: {float} }
	 */
	this.parse_coordinates = function( location ) {
		var coordinates = {};
		location.toString().replace(
			/([-]*[0-9.]+)[,]+[ ]*([-]*[0-9.]+)/,
			function() {
				coordinates = { lat: parseFloat( arguments[1] ), lng: parseFloat( arguments[2] ) };
				return false;
			}
		);
		return coordinates;
	}
};

var bws_bkng_maps = new BwsMapHandler();

