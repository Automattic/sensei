import { addQueryArgs } from '@wordpress/url';
import { API_BASE_PATH } from './constants';
import { fetchFromAPI, setStepData } from './actions';
import { normalizeImportData } from './normalizer';

export function* getStepData( step, jobId, shouldResolve ) {
	if ( ! shouldResolve ) {
		return;
	}

	const data = yield fetchFromAPI( {
		path: addQueryArgs( API_BASE_PATH, { job_id: jobId } ),
	} );

	return setStepData( step, normalizeImportData( data ) );
}
