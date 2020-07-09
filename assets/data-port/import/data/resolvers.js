import { fetchFromAPI, setImportLog, errorFetchImportLog } from './actions';
import { buildJobEndpointUrl } from '../helpers/url';

export function* getLogsBySeverity( jobId ) {
	try {
		const data = yield fetchFromAPI( {
			path: buildJobEndpointUrl( jobId, [ 'logs' ] ),
		} );

		yield setImportLog( data );
	} catch ( error ) {
		yield errorFetchImportLog( error );
	}
}
