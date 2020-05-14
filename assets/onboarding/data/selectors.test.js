import {
	getUsageTracking,
	isFetchingUsageTracking,
	isSubmittingUsageTracking,
	errorUsageTracking,
} from './selectors';

describe( 'Setup wizard selectors', () => {
	it( 'Get usage tracking selector', () => {
		const state = {
			welcome: {
				data: {
					usageTracking: true,
				},
			},
		};

		expect( getUsageTracking( state ) ).toBeTruthy();
	} );

	it( 'Is fetching usage tracking selector', () => {
		const state = {
			welcome: {
				isFetching: true,
			},
		};

		expect( isFetchingUsageTracking( state ) ).toBeTruthy();
	} );

	it( 'Is submitting usage tracking selector', () => {
		const state = {
			welcome: {
				isSubmitting: true,
			},
		};

		expect( isSubmittingUsageTracking( state ) ).toBeTruthy();
	} );

	it( 'Error usage tracking selector', () => {
		const error = { err: 'Error message' };
		const state = {
			welcome: {
				error,
			},
		};

		expect( errorUsageTracking( state ) ).toEqual( error );
	} );
} );
