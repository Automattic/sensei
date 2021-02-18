/**
 * Internal dependencies
 */
import { SET_IMPORT_LOG, ERROR_FETCH_IMPORT_LOG } from './constants';
import { composeFetchAction } from '../../../shared/data/store-helpers';
import { fetchFromAPI } from './actions';
import { buildJobEndpointUrl } from '../helpers/url';

export const getLogsBySeverity = composeFetchAction(
	null,
	function* ( jobId ) {
		if ( ! jobId ) {
			return;
		}

		return yield fetchFromAPI( {
			path: buildJobEndpointUrl( jobId, [ 'logs' ] ),
		} );
	},
	SET_IMPORT_LOG,
	ERROR_FETCH_IMPORT_LOG
);
