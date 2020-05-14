import {
	FETCH_USAGE_TRACKING,
	SUBMIT_USAGE_TRACKING,
	ERROR_USAGE_TRACKING,
	SET_USAGE_TRACKING,
} from './constants'
import reducer from './reducer';

describe( 'Setup wizard reducer', () => {
	it( 'FETCH_USAGE_TRACKING action', () => {
		const state = reducer( undefined, {
			type: FETCH_USAGE_TRACKING,
		} );

		expect( state.welcome.isFetching ).toBeTruthy();
	} );

	it( 'SUBMIT_USAGE_TRACKING action', () => {
		const state = reducer( undefined, {
			type: SUBMIT_USAGE_TRACKING,
		} );

		expect( state.welcome.isSubmitting ).toBeTruthy();
	} );

	it( 'ERROR_USAGE_TRACKING action', () => {
		const error = { err: 'Error message' };
		const state = reducer( undefined, {
			type: ERROR_USAGE_TRACKING,
			error,
		} );

		expect( state.welcome.error ).toEqual( error );
	} );

	it( 'SET_USAGE_TRACKING action', () => {
		const state = reducer( undefined, {
			type: SET_USAGE_TRACKING,
			usageTracking: true,
		} );

		expect( state.welcome.data.usageTracking ).toBeTruthy();
	} );
} );
