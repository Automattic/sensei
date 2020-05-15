import {
	API_BASE_PATH,
	FETCH_FROM_API,
	START_FETCH_SETUP_WIZARD_DATA,
	SET_SETUP_WIZARD_DATA,
	START_SUBMIT_SETUP_WIZARD_DATA,
	SUCCESS_SUBMIT_SETUP_WIZARD_DATA,
	ERROR_SUBMIT_SETUP_WIZARD_DATA,
	SET_WELCOME_STEP_DATA,
} from './constants';
import {
	fetchFromAPI,
	fetchSetupWizardData,
	setSetupWizardData,
	startSubmit,
	successSubmit,
	errorSubmit,
	submitWelcomeStep,
	setWelcomeStepData,
} from './actions';

describe( 'Setup wizard actions', () => {
	it( 'Should return the fetch from API action', () => {
		const requestObject = { path: '/test' };
		const expectedAction = {
			type: FETCH_FROM_API,
			request: requestObject,
		};

		expect( fetchFromAPI( requestObject ) ).toEqual( expectedAction );
	} );

	it( 'Should generate the fetch setup wizard data action', () => {
		const gen = fetchSetupWizardData();

		// Start fetch action.
		const expectedStartFetchAction = {
			type: START_FETCH_SETUP_WIZARD_DATA,
		};
		expect( gen.next().value ).toEqual( expectedStartFetchAction );

		// Fetch action.
		const expectedFetchAction = {
			type: FETCH_FROM_API,
			request: {
				path: API_BASE_PATH + 'welcome',
			},
		};
		expect( gen.next().value ).toEqual( expectedFetchAction );

		// Set data action.
		const welcomeObject = { x: 1 };
		const expectedSetDataAction = {
			type: SET_SETUP_WIZARD_DATA,
			data: { welcome: welcomeObject },
		};
		expect( gen.next( welcomeObject ).value ).toEqual(
			expectedSetDataAction
		);
	} );

	it( 'Should return the set setup wizard data action', () => {
		const data = { x: 1 };
		const expectedAction = {
			type: SET_SETUP_WIZARD_DATA,
			data,
		};

		expect( setSetupWizardData( data ) ).toEqual( expectedAction );
	} );

	it( 'Should return the start submit action', () => {
		const expectedAction = { type: START_SUBMIT_SETUP_WIZARD_DATA };

		expect( startSubmit() ).toEqual( expectedAction );
	} );

	it( 'Should return the success submit action', () => {
		const expectedAction = { type: SUCCESS_SUBMIT_SETUP_WIZARD_DATA };

		expect( successSubmit() ).toEqual( expectedAction );
	} );

	it( 'Should return the error submit action', () => {
		const error = { err: 'Error' };
		const expectedAction = {
			type: ERROR_SUBMIT_SETUP_WIZARD_DATA,
			error,
		};

		expect( errorSubmit( error ) ).toEqual( expectedAction );
	} );

	it( 'Should generate the submit welcome step action', () => {
		const usageTracking = true;
		const gen = submitWelcomeStep( usageTracking );

		// Start submit action.
		const expectedStartSubmitAction = {
			type: START_SUBMIT_SETUP_WIZARD_DATA,
		};
		expect( gen.next().value ).toEqual( expectedStartSubmitAction );

		// Submit action.
		const expectedSubmitAction = {
			type: FETCH_FROM_API,
			request: {
				path: API_BASE_PATH + 'welcome',
				method: 'POST',
				data: {
					usage_tracking: usageTracking,
				},
			},
		};
		expect( gen.next().value ).toEqual( expectedSubmitAction );

		// Success action.
		const expectedSuccessAction = {
			type: SUCCESS_SUBMIT_SETUP_WIZARD_DATA,
		};
		expect( gen.next().value ).toEqual( expectedSuccessAction );

		// Set data action.
		const expectedSetDataAction = {
			type: SET_WELCOME_STEP_DATA,
			data: { usage_tracking: usageTracking },
		};
		expect( gen.next().value ).toEqual( expectedSetDataAction );
	} );

	it( 'Should catch error on the submit welcome step action', () => {
		const gen = submitWelcomeStep( true );

		// Start submit action.
		gen.next();

		// Fetch action.
		gen.next();

		// Error action.
		const error = { msg: 'Error' };
		const expectedErrorAction = {
			type: ERROR_SUBMIT_SETUP_WIZARD_DATA,
			error,
		};
		expect( gen.throw( error ).value ).toEqual( expectedErrorAction );
	} );

	it( 'Should return the set welcome step data action', () => {
		const data = { usage_tracking: true };
		const expectedAction = {
			type: SET_WELCOME_STEP_DATA,
			data,
		};

		expect( setWelcomeStepData( data ) ).toEqual( expectedAction );
	} );
} );
