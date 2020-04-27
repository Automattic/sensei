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
 * @return {string} The value in the query string.
 */
const getQueryString = ( name ) =>
	new URLSearchParams( window.location.search ).get( name );

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
 * Get current route from URL or first route.
 *
 * @param {string} queryStringName Query string name that the route is associated.
 * @param {Array}  routes          Routes list.
 *
 * @return {string} Current route key.
 */
export const getCurrentRouteFromURL = ( queryStringName, routes = null ) =>
	getQueryString( queryStringName ) || routes[ 0 ].key;
