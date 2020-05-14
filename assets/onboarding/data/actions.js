import {
	API_BASE_PATH,
	FETCH_FROM_API,
	FETCH_USAGE_TRACKING,
	SUBMIT_USAGE_TRACKING,
	ERROR_USAGE_TRACKING,
	SET_USAGE_TRACKING,
} from './constants';

/**
 * @typedef  {Object} FetchFromAPIAction
 * @property {string} type               Action type.
 * @property {Object} request            Object that is used to fetch.
 */
/**
 * Fetch action creator.
 *
 * @param {Object} request Object that is used to fetch.
 *
 * @return {FetchFromAPIAction} Fetch action.
 */
export const fetchFromAPI = ( request ) => ( {
	type: FETCH_FROM_API,
	request,
} );

/**
 * @typedef  {Object} FetchUsageTrackingAction
 * @property {string} type                     Action type.
 */
/**
 * Fetch usage tracking action creator.
 *
 * @return {FetchUsageTrackingAction} Fetch usage tracking action.
 */
export const fetchUsageTracking = () => ( {
	type: FETCH_USAGE_TRACKING,
} );

/**
 * Submit usage tracking action creator.
 *
 * @param {boolean} usageTracking Usage tracking.
 */
export function* submitUsageTracking( usageTracking ) {
	yield { type: SUBMIT_USAGE_TRACKING };

	try {
		yield fetchFromAPI( {
			path: API_BASE_PATH + 'welcome',
			method: 'POST',
			data: { usage_tracking: usageTracking },
		} );
		yield setUsageTracking( usageTracking );
	} catch ( error ) {
		yield errorUsageTracking( error );
	}
}

/**
 * @typedef  {Object}         ErrorUsageTrackingAction
 * @property {string}         type                     Action type.
 * @property {string|boolean} error                    Usage tracking error or false.
 */
/**
 * Error usage tracking action creator.
 *
 * @param {string|boolean} error Usage tracking error or false.
 *
 * @return {ErrorUsageTrackingAction} Error usage tracking action.
 */
export const errorUsageTracking = ( error ) => ( {
	type: ERROR_USAGE_TRACKING,
	error,
} );

/**
 * @typedef  {Object}  SetUsageTrackingAction
 * @property {string}  type                   Action type.
 * @property {boolean} usageTracking          Usage tracking.
 */
/**
 * Set usage tracking action creator.
 *
 * @param {boolean} usageTracking Usage tracking.
 *
 * @return {SetUsageTrackingAction} Set usage tracking action.
 */
export const setUsageTracking = ( usageTracking ) => ( {
	type: SET_USAGE_TRACKING,
	usageTracking,
} );
