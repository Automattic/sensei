/**
 * Internal dependencies
 */
import * as selectors from '../selectors';

const messages = [ { id: 1 }, { id: 2 } ];
const isFetching = true;
const error = { message: 'Uh oh!' };
const state = {
	messages,
	error,
	isFetching,
};

describe( 'getMessages', () => {
	it( 'gets the messages', () => {
		expect( selectors.getMessages( state ) ).toBe( messages );
	} );
} );

describe( 'isFetching', () => {
	it( 'gets the isFetching flag', () => {
		expect( selectors.isFetching( state ) ).toBe( isFetching );
	} );
} );

describe( 'getError', () => {
	it( 'gets the error', () => {
		expect( selectors.getError( state ) ).toBe( error );
	} );
} );
