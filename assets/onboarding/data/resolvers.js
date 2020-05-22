import { API_BASE_PATH } from './constants';
import { fetchFromAPI, setStepData } from './actions';

export function* getStepData( step, shouldResolve ) {
	if ( ! shouldResolve ) {
		return;
	}

	const data = yield fetchFromAPI( {
		path: API_BASE_PATH + step,
	} );

	return setStepData( 'features', data );
}
