/**
 * Internal dependencies
 */
import { API_BASE_PATH } from '../data/constants';

/**
 * Build a URL for a job specific route.
 *
 * @param {string?} jobId Job identifier.
 * @param {Array?}  parts Parts of the URL.
 * @return {string} Combined URL.
 */
export const buildJobEndpointUrl = ( jobId, parts = null ) => {
	const path = [
		...( jobId ? [ jobId ] : [] ),
		...( parts ? parts : [] ),
	].join( '/' );

	return API_BASE_PATH + path;
};
