
/**
 * Formats numbers according to a given format - PHP function equivalent
 * @link http://php.net/manual/en/function.number-format.php
 * @link http://javascript.ru/php/number_format
 * @param  {mixed}    number         The number being formatted.
 * @param  {int}      decimals       Sets the number of decimal points.
 * @param  {string}   dec_point      Sets the separator for the decimal point.
 * @param  {string}   thousands_sep  Sets the thousands separator.
 * @return {string}                  A formatted version of number.
 */
function bws_bkng_number_format( number, decimals, dec_point, thousands_sep ) {
	var i, j, kw, kd, km;

	if ( typeof decimals == 'undefined' )
		decimals = 2;

	if( typeof dec_point == 'undefined' )
		dec_point = bws_bkng.dec_sep;

	if( typeof thousands_sep == 'undefined' )
		thousands_sep = bws_bkng.thou_sep;

	i = parseInt( number = ( +number || 0 ).toFixed( decimals ) ) + "";
	j = i.length > 3 ? i.length % 3 : 0;
	km = j ? i.substr( 0, j ) + thousands_sep : "";
	kw = i.substr( j ).replace( /(\d{3})(?=\d)/g, "$1" + thousands_sep );
	kd = decimals ? dec_point + Math.abs( number - i ).toFixed( decimals ).replace( /-/, 0 ).slice( 2 ) : "";
	return km + kw + kd;
}

/**
 * Converts a string to a number
 * @param  {string}    string         The raw string
 * @param  {boolean}   to_float       Wether to return integer or floating-point number
 */
function bws_bkng_str_to_number( string, to_float ) {
	to_float = !! to_float;
	try {
		if ( typeof string == 'number' )
			return string;

		var integer, decimal,
			parts = string.split( bws_bkng.dec_sep );

		integer = parts[0].replace( new RegExp( bws_bkng.thou_sep, 'g' ), '' );
		decimal = typeof parts[1] == 'undefined' ? '' : '.' + parts[1];

		return to_float ? parseFloat( integer + decimal ) : parseInt( integer + decimal );
	} catch ( error ) {
		return false;
	}
}

/**
 * Formats numbers according to the given format - PHP function equivalent
 * @link http://php.net/manual/en/function.number-format.php
 * @link http://javascript.ru/php/number_format
 * @param  {mixed}    number         The number being formatted.
 * @param  {int}      decimals       Sets the number of decimal points.
 * @param  {string}   dec_point      Sets the separator for the decimal point.
 * @param  {string}   thousands_sep  Sets the thousands separator.
 * @return {string}                  A formatted version of number.
 */
function bws_bkng_sting_to_number( value, dec_point, thousands_sep ) {
	var dec = new RegExp( '\\' + dec_point, 'g' ),
		thousands = new RegExp( '\\' + thousands_sep, 'g' );

	if ( 'string' !== typeof value ) {
		return value;
	}

	return +value.replace( dec, '.' ).replace( thousands, '' );
}

/**
 * Converts query string with $_GET parameters into object
 * @type   {function}
 * @param  {string}    url    processed query string
 * @return {object|boolean}    An list of data in the format {query_key}=>{value}, false otherwise
 */
function bws_bkng_parse_query_string( url ) {
	try{
		var url_parts = url.split( '?' ),
			query_pairs, pair, query = {};

		if ( typeof url_parts[1] == 'undefined' )
			return false;

		query_pairs = url_parts[1].split( '&' );

		for ( var i = 0; i < query_pairs.length; i ++ ) {
			pair = query_pairs[ i ].split( '=' );

			if ( pair.length != 2 )
				continue;

			query[ pair[0] ] = pair[1];
		}

		return query;

	} catch( error ) {
		return false;
	}
}

function BwsBkngHooksHandler() {
	this.hooks = {};
}
BwsBkngHooksHandler.prototype.add_filter = function( event, callback ) {
	var callback_type = typeof callback, slug;

	if ( ! jQuery.inArray( callback_type, [ 'string', 'function' ] ) )
		return;

	if ( typeof this.hooks[ event ] == 'undefined' )
		this.hooks[ event ] = [];

	this.hooks[ event ].push( callback );

	return this;
};
BwsBkngHooksHandler.prototype.apply_filters = function() {

	if ( arguments.length < 2 )
		return false;

	var args   = Array.apply( null, arguments ); /* object to array converting */
		event  = args[0],
		data   = args[1],
		extras = args.slice(2);

	if ( typeof this.hooks[ event ] == 'undefined' )
		return data;

	jQuery.each( this.hooks[ event ], function( index, callback ) {
		data = callback( data, extras );
	});

	return data;
};

var BwsBkngHooks = new BwsBkngHooksHandler();
