import {
	API_BASE_PATH,
	FETCH_FROM_API,
	FETCH_USAGE_TRACKING,
	SET_USAGE_TRACKING,
} from './constants';

import { getUsageTracking } from './resolvers';

describe( 'Setup wizard resolvers', () => {
	it( 'Get usage tracking resolver', () => {
		const gen = getUsageTracking( true );

		// Start fetch.
		const expectedSubmitAction = { type: FETCH_USAGE_TRACKING };
		expect( gen.next().value ).toEqual( expectedSubmitAction );

		// Call fetch action to POST.
		const expectedFetchAction = {
			type: FETCH_FROM_API,
			request: {
				path: API_BASE_PATH + 'welcome',
			},
		};
		expect( gen.next().value ).toEqual( expectedFetchAction );

		// Set usage tracking.
		const expectedSetAction = {
			type: SET_USAGE_TRACKING,
			usageTracking: true,
		};
		expect( gen.next( { usage_tracking: true } ).value ).toEqual(
			expectedSetAction
		);

		// Generator is done.
		expect( gen.next().done ).toBeTruthy();
	} );
} );
