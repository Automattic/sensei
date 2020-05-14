import {
	API_BASE_PATH,
	FETCH_FROM_API,
	FETCH_USAGE_TRACKING,
	SUBMIT_USAGE_TRACKING,
	ERROR_USAGE_TRACKING,
	SET_USAGE_TRACKING,
} from './constants';
import {
	fetchFromAPI,
	fetchUsageTracking,
	submitUsageTracking,
	errorUsageTracking,
	setUsageTracking,
} from './actions';

describe( 'Setup wizard actions', () => {
	it( 'Fetch from API action', () => {
		const requestObject = { path: '/test' };
		const expectedAction = {
			type: FETCH_FROM_API,
			request: requestObject,
		};

		expect( fetchFromAPI( requestObject ) ).toEqual( expectedAction );
	} );

	it( 'Fetch usage tracking action', () => {
		const expectedAction = {
			type: FETCH_USAGE_TRACKING,
		};

		expect( fetchUsageTracking() ).toEqual( expectedAction );
	} );

	it( 'Submit usage tracking action', () => {
		const gen = submitUsageTracking( true );

		// Start submit.
		const expectedSubmitAction = { type: SUBMIT_USAGE_TRACKING };
		expect( gen.next().value ).toEqual( expectedSubmitAction );

		// Call fetch action to POST.
		const expectedFetchAction = {
			type: FETCH_FROM_API,
			request: {
				path: API_BASE_PATH + 'welcome',
				method: 'POST',
				data: { usage_tracking: true },
			},
		};
		expect( gen.next().value ).toEqual( expectedFetchAction );

		// Set usage tracking.
		const expectedSetAction = {
			type: SET_USAGE_TRACKING,
			usageTracking: true,
		};
		expect( gen.next().value ).toEqual( expectedSetAction );

		// Generator is done.
		expect( gen.next().done ).toBeTruthy();
	} );

	it( 'Error usage tracking action', () => {
		const error = { err: 'Error message' };
		const expectedAction = {
			type: ERROR_USAGE_TRACKING,
			error,
		};

		expect( errorUsageTracking( error ) ).toEqual( expectedAction );
	} );

	it( 'Set usage tracking action', () => {
		const expectedAction = {
			type: SET_USAGE_TRACKING,
			usageTracking: true,
		};

		expect( setUsageTracking( true ) ).toEqual( expectedAction );
	} );
} );
