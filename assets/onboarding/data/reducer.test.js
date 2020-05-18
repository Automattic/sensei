import {
	START_FETCH_SETUP_WIZARD_DATA,
	SET_SETUP_WIZARD_DATA,
	START_SUBMIT_SETUP_WIZARD_DATA,
	SUCCESS_SUBMIT_SETUP_WIZARD_DATA,
	ERROR_SUBMIT_SETUP_WIZARD_DATA,
	SET_WELCOME_STEP_DATA,
} from './constants';
import reducer from './reducer';

describe( 'Setup wizard reducer', () => {
	it( 'Should set isFetching to true on START_FETCH_SETUP_WIZARD_DATA action', () => {
		const state = reducer( undefined, {
			type: START_FETCH_SETUP_WIZARD_DATA,
		} );

		expect( state.isFetching ).toBeTruthy();
	} );

	it( 'Should set data and set states to false on SET_SETUP_WIZARD_DATA action', () => {
		const data = { a: 1 };
		const state = reducer(
			{
				isFetching: true,
				isSubmitting: true,
				error: { msg: 'Error' },
			},
			{
				type: SET_SETUP_WIZARD_DATA,
				data,
			}
		);

		const expectedState = {
			isFetching: false,
			isSubmitting: false,
			error: false,
			data,
		};
		expect( state ).toEqual( expectedState );
	} );

	it( 'Should set isSubmitting to true on START_SUBMIT_SETUP_WIZARD_DATA action', () => {
		const state = reducer(
			{
				isSubmitting: false,
				error: { msg: 'Error' },
			},
			{
				type: START_SUBMIT_SETUP_WIZARD_DATA,
			}
		);

		expect( state.isSubmitting ).toBeTruthy();
		expect( state.error ).toBeFalsy();
	} );

	it( 'Should set isSubmitting to false on SUCCESS_SUBMIT_SETUP_WIZARD_DATA action', () => {
		const state = reducer(
			{
				isSubmitting: true,
			},
			{
				type: SUCCESS_SUBMIT_SETUP_WIZARD_DATA,
			}
		);

		expect( state.isSubmitting ).toBeFalsy();
	} );

	it( 'Should set error on ERROR_SUBMIT_SETUP_WIZARD_DATA action', () => {
		const error = { msg: 'Error' };
		const state = reducer(
			{
				isSubmitting: true,
			},
			{
				type: ERROR_SUBMIT_SETUP_WIZARD_DATA,
				error,
			}
		);

		expect( state.isSubmitting ).toBeFalsy();
		expect( state.error ).toEqual( error );
	} );

	it( 'Should set the welcome data on SET_WELCOME_STEP_DATA action', () => {
		const data = { usage_tracking: true };
		const state = reducer( undefined, {
			type: SET_WELCOME_STEP_DATA,
			data,
		} );

		expect( state.data.welcome ).toEqual( data );
	} );

	it( 'Should return the current state for unknown types', () => {
		const currentState = { x: 1 };
		const state = reducer( currentState, {
			type: 'UNKNOWN',
		} );

		expect( state ).toEqual( currentState );
	} );
} );
