/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { FETCH_FROM_API, APPLY_STEP_DATA } from './constants';
import { logEvent } from '../../shared/helpers/log-event';

export default {
	/**
	 * Fetch control.
	 *
	 * @param {{request: Object}} action Action with the request object that is used to fetch.
	 *
	 * @return {Promise} API fetch promise.
	 */
	[ FETCH_FROM_API ]: ( { request } ) => apiFetch( request ),
	[ APPLY_STEP_DATA ]: ( { step, data } ) => {
		switch ( step ) {
			case 'welcome': {
				logEvent.enable( data.usage_tracking );
				break;
			}
		}
	},
};
