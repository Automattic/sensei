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
		const messages = [ { id: 1 }, { id: 2 } ];
		const error = { message: 'Whoops!' };

		expect( actions.receiveMessages( messages, error ) ).toEqual( {
			type: 'RECEIVE_MESSAGES',
			messages,
			error,
		} );
	} );
} );
