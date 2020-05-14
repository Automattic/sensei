import { API_BASE_PATH } from './constants';
import { fetchFromAPI, fetchUsageTracking, setUsageTracking } from './actions';

/**
 * Get usage tracking resolver.
 */
export function* getUsageTracking() {
	yield fetchUsageTracking();
	const usageTracking = yield fetchFromAPI( {
		path: API_BASE_PATH + 'welcome',
	} );
	return setUsageTracking( usageTracking.usage_tracking );
}
