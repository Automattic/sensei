/**
 * Internal dependencies
 */
import reducer from '../reducer';
import * as actions from '../actions';

describe( 'reducer', () => {
	it( 'sets the default state', () => {
		const state = reducer( undefined, {} );
		expect( state ).toEqual( {
			messages: [],
			isFetching: false,
			error: null,
		} );
	} );

	describe( 'on FETCH_MESSAGES', () => {
		const action = actions.fetchMessages();

		it( 'returns with isFetching set to true', () => {
			const state = reducer( {}, action );
			expect( state.isFetching ).toBeTruthy();
		} );
	} );

	describe( 'on RECEIVE_MESSAGES', () => {
		const messages = [ { id: 1 }, { id: 2 } ];
		const error = { message: 'Uh oh!' };
		const action = actions.receiveMessages( messages, error );

		it( 'returns with isFetching set to false', () => {
			const state = reducer( {}, action );
			expect( state.isFetching ).toBeFalsy();
		} );

		it( 'returns with messages', () => {
			const state = reducer( {}, action );
			expect( state.messages ).toEqual( messages );
		} );

		it( 'returns with error', () => {
			const state = reducer( {}, action );
			expect( state.error ).toEqual( error );
		} );
	} );
} );
