/**
 * Get parameter from URL.
 *
 * @param {string} name Name of the param to get.
 *
 * @return {string|null} The value in the param. If it's empty, return null.
 */
export const getParam = ( name ) =>
	new URLSearchParams( window.location.search ).get( name ) || null;

/**
 * Update query string.
 *
 * @param {string}  paramName  Param name to be added to the URL.
 * @param {string}  paramValue Param value to be added to the URL.
 * @param {boolean} replace    Flag if it should replace the state. Otherwise it'll push a new.
 */
export const updateQueryString = ( paramName, paramValue, replace = false ) => {
	const { search } = window.location;
	const historyMethod = replace ? 'replaceState' : 'pushState';
	const searchParams = new URLSearchParams( search );

	if ( null === paramValue ) {
		searchParams.delete( paramName );
	} else {
		searchParams.set( paramName, paramValue );
	}
	window.history[ historyMethod ]( {}, '', `?${ searchParams.toString() }` );
};
