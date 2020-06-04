import {
	isFetching,
	getFetchError,
	isSubmitting,
	getSubmitError,
	getStepData,
	getNavigationSteps,
	isCompleteStep,
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

		expect( getStepData( state, 'welcome' ) ).toEqual( {
			usage_tracking: true,
		} );
	} );

	it( 'Should get navigation data', () => {
		const state = {
			data: {
				completedSteps: [ 'welcome' ],
			},
		};

		expect( getNavigationSteps( state )[ 0 ] ).toHaveProperty(
			'isComplete',
			true
		);
		expect( getNavigationSteps( state )[ 0 ] ).toHaveProperty(
			'isNext',
			false
		);

		expect( getNavigationSteps( state )[ 1 ] ).toHaveProperty(
			'isComplete',
			false
		);
		expect( getNavigationSteps( state )[ 1 ] ).toHaveProperty(
			'isNext',
			true
		);
	} );

	it( 'Should return whether step is complete or not', () => {
		const state = {
			data: {
				completedSteps: [ 'welcome' ],
			},
		};

		expect( isCompleteStep( state, 'welcome' ) ).toBeTruthy();
		expect( isCompleteStep( state, 'other' ) ).toBeFalsy();
	} );
} );
