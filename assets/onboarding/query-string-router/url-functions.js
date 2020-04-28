/**
 * Add query string to URL with pushState.
 *
 * @param {string} queryStringName  Query string name to be added to the URL.
 * @param {string} queryStringValue Query string value to be added to the URL.
 */
const pushQueryStringState = ( queryStringName, queryStringValue ) => {
	const { search } = window.location;
	const searchParams = new URLSearchParams( search );

	searchParams.set( queryStringName, queryStringValue );
	window.history.pushState( {}, '', `?${ searchParams.toString() }` );
};

/**
 * Get query string from URL.
 *
 * @param {string} name  Name of the query string to get.
 *
 * @return {string|null} The value in the query string. If it's empty, return null.
 */
const getQueryString = ( name ) =>
	new URLSearchParams( window.location.search ).get( name ) || null;

/**
 * Update URL with new route.
 *
 * @param {string} queryStringName Query string name that the route is associated.
 * @param {string} newRoute        Text that represents the new route.
 */
export const updateRouteURL = ( queryStringName, newRoute ) => {
	pushQueryStringState( queryStringName, newRoute );
};

/**
 * Get current route from URL.
 *
 * @param {string} queryStringName Query string name that the route is associated.
 *
 * @return {string} Current route key.
 */
export const getCurrentRouteFromURL = ( queryStringName ) =>
	getQueryString( queryStringName );
