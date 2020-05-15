import {
	isFetchingSetupWizardData,
	isSubmitting,
	getSubmitError,
	getUsageTracking,
} from './selectors';

describe( 'Setup wizard selectors', () => {
	it( 'Should get the is feching data', () => {
		const state = {
			isFetching: true,
		};

		expect( isFetchingSetupWizardData( state ) ).toBeTruthy();
	} );

	it( 'Should get the is submitting data', () => {
		const state = {
			isSubmitting: true,
		};

		expect( isSubmitting( state ) ).toBeTruthy();
	} );

	it( 'Should get the submit error data', () => {
		const error = { err: 'Error message' };
		const state = {
			error,
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
