/**
 * Add param to the URL with pushState.
 *
 * @param {string}  paramName  Param name to be added to the URL.
 * @param {string}  paramValue Param value to be added to the URL.
 * @param {boolean} replace    Flag if it should replace the state. Otherwise it'll push a new.
 */
const pushQueryStringState = ( paramName, paramValue, replace = false ) => {
	const { search } = window.location;
	const historyMethod = replace ? 'replaceState' : 'pushState';
	const searchParams = new URLSearchParams( search );

	searchParams.set( paramName, paramValue );
	window.history[ historyMethod ]( {}, '', `?${ searchParams.toString() }` );
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
 * @param {string}  paramName Param name that the route is associated.
 * @param {string}  newRoute  Text that represents the new route.
 * @param {boolean} replace   Flag if it should replace the state. Otherwise it'll push a new.
 */
export const updateRouteURL = ( paramName, newRoute, replace = false ) => {
	pushQueryStringState( paramName, newRoute, replace );
};

/**
 * Get current route from URL.
 *
 * @param {string} paramName Param name that the route is associated.
 *
 * @return {string} Current route key.
 */
export const getCurrentRouteFromURL = ( paramName ) => getParam( paramName );
