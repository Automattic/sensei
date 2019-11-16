/**
 * Internal dependencies
 */
import * as actions from '../actions';

describe( 'fetchMessages', () => {
	it( 'builds an action object', () => {
		expect( actions.fetchMessages() ).toEqual( {
			type: 'FETCH_MESSAGES',
		} );
	} );
} );

describe( 'receiveMessages', () => {
	it( 'builds an action object', () => {
		let messages = [ { id: 1 }, { id: 2 } ];
		let error = { message: 'Whoops!' };

		expect( actions.receiveMessages( messages, error ) ).toEqual( {
			type: 'RECEIVE_MESSAGES',
			messages,
			error,
		} );
	} );
} );
