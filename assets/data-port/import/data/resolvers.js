import { fetchFromAPI, setImportLog } from './actions';
import { buildJobEndpointUrl } from '../helpers/url';

export function* getLogsBySeverity( jobId ) {
	const data = yield fetchFromAPI( {
		path: buildJobEndpointUrl( jobId, [ 'logs' ] ),
	} );

	return setImportLog( data );
}
