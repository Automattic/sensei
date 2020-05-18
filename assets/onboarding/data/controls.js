import apiFetch from '@wordpress/api-fetch';

import { FETCH_FROM_API } from './constants';

export default {
	/**
	 * Fetch control.
	 *
	 * @param  {{request: Object}} action Action with the request object that is used to fetch.
	 *
	 * @return {Promise} API fetch promise.
	 */
	[ FETCH_FROM_API ]: ( { request } ) => apiFetch( request ),
};
