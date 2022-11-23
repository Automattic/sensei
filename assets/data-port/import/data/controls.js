/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { FETCH_FROM_API, WAIT } from './constants';

export default {
	/**
	 * Fetch control.
	 *
	 * @param {{request: Object}} action Action with the request object that is used to fetch.
	 *
	 * @return {Promise} API fetch promise.
	 */
	[ FETCH_FROM_API ]: ( { request } ) => apiFetch( request ),
	[ WAIT ]: ( { timeout } ) =>
		new Promise( ( resolve ) => setTimeout( resolve, timeout ) ),
};
