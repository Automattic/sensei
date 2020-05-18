import {
	isFetching,
	getFetchError,
	isSubmitting,
	getSubmitError,
	getUsageTracking,
} from './selectors';

describe( 'Setup wizard selectors', () => {
	it( 'Should get the is feching data', () => {
		const state = {
			isFetching: true,
		};

		expect( isFetching( state ) ).toBeTruthy();
	} );

	it( 'Should get the fetch error', () => {
		const error = { err: 'Error message' };
		const state = {
			fetchError: error,
		};

		expect( getFetchError( state ) ).toEqual( error );
	} );

	it( 'Should get the is submitting data', () => {
		const state = {
			isSubmitting: true,
		};

		expect( isSubmitting( state ) ).toBeTruthy();
	} );

	it( 'Should get the submit error', () => {
		const error = { err: 'Error message' };
		const state = {
			submitError: error,
		};

		expect( getSubmitError( state ) ).toEqual( error );
	} );

	it( 'Should get usage tracking data', () => {
		const state = {
			data: {
				welcome: {
					usage_tracking: true,
				},
			},
		};

		expect( getUsageTracking( state ) ).toBeTruthy();
	} );
} );
