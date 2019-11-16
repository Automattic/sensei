/**
 * WordPress dependencies.
 */
import { select, subscribe } from '@wordpress/data';

/**
 * Internal dependencies
 */
import MESSAGES_STORE from '../';
import * as resolvers from '../resolvers';

describe( 'getMessages', () => {
	const originalFetch = window.fetch;
	const storeSelect = select( MESSAGES_STORE );

	beforeEach( () => {
		window.fetch = jest.fn();
	} );

	afterEach( () => {
		window.fetch = originalFetch;
	} );

	it( 'sets isFetching to true', () => {
		resolvers.getMessages();
		expect( storeSelect.isFetching() ).toBeTruthy();
	} );

	it( 'triggers an API request for the messages', () => {
		resolvers.getMessages();

		let fetchPath = window.fetch.mock.calls[0][0];
		expect( fetchPath ).toMatch( /^\/wp\/v2\/sensei-messages/ );
	} );

	describe( 'on success', () => {
		let messages = [ { id: 1 }, { id: 2 } ];

		beforeEach( () => {
			window.fetch.mockReturnValue( Promise.resolve( {
				status: 200,
				json() {
					return Promise.resolve( messages );
				},
			} ) );
		} );

		it( 'sets the messages from the API', done => {
			resolvers.getMessages();

			// When API request completes, the messages should be populated.
			let didFinish = false;
			subscribe( () => {
				if ( didFinish ) {
					return;
				}

				if ( storeSelect.getMessages().length ) {
					expect( storeSelect.isFetching() ).toBeFalsy();
					expect( storeSelect.getMessages() ).toEqual( messages );
					didFinish = true;
					done();
				}
			} );
		} );
	} );

	describe( 'on error', () => {
		let error = { message: 'Oh noes!' };

		beforeEach( () => {
			window.fetch.mockReturnValue( Promise.resolve( {
				status: 500,
				json() {
					return Promise.resolve( error );
				},
			} ) );
		} );

		it( 'dispaches RECEIVE_MESSAGES with the error from the API', () => {
			resolvers.getMessages();

			// When API request completes, the messages should be populated.
			let didFinish = false;
			subscribe( () => {
				if ( didFinish ) {
					return;
				}

				if ( storeSelect.getError() ) {
					expect( storeSelect.isFetching() ).toBeFalsy();
					expect( storeSelect.getError() ).toEqual( error );
					done();
				}
			} );
		} );
	} );
} ) ;
