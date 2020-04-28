/**
 * Add param to the URL with pushState.
 *
 * @param {string} paramName  Param name to be added to the URL.
 * @param {string} paramValue Param value to be added to the URL.
 */
const pushQueryStringState = ( paramName, paramValue ) => {
	const { search } = window.location;
	const searchParams = new URLSearchParams( search );

	searchParams.set( paramName, paramValue );
	window.history.pushState( {}, '', `?${ searchParams.toString() }` );
};

/**
 * Get parameter from URL.
 *
 * @param {string} name  Name of the param to get.
 *
 * @return {string|null} The value in the param. If it's empty, return null.
 */
const getParam = ( name ) =>
	new URLSearchParams( window.location.search ).get( name ) || null;

/**
 * Update URL with new route.
 *
 * @param {string} paramName Param name that the route is associated.
 * @param {string} newRoute  Text that represents the new route.
 */
export const updateRouteURL = ( paramName, newRoute ) => {
	pushQueryStringState( paramName, newRoute );
};

/**
 * Get current route from URL.
 *
 * @param {string} paramName Param name that the route is associated.
 *
 * @return {string} Current route key.
 */
export const getCurrentRouteFromURL = ( paramName ) => getParam( paramName );
