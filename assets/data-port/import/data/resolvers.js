import { fetchFromAPI, setStepData } from './actions';
import { normalizeImportData } from './normalizer';
import { buildJobEndpointUrl } from '../helpers/url';

export function* getStepData( step, jobId, shouldResolve ) {
	if ( ! shouldResolve ) {
		return;
	}

	const data = yield fetchFromAPI( {
		path: buildJobEndpointUrl( jobId ),
	} );

	return setStepData( step, normalizeImportData( data ) );
}
