/**
 * Internal dependencies
 */
import httpClient from '.';
/**
 * External dependencies
 */
import nock from 'nock';

describe( 'http-client', () => {
	beforeEach( () => {
		jest.useRealTimers();
	} );
	it( 'Should make a request to a rest route', async () => {
		nock.disableNetConnect();
		nock( 'http://localhost' )
			.post( '/', { foo: 'bar' } )
			.query( {
				rest_route: '/sensei-internal/v1/some-internal-api',
				_locale: 'user',
			} )
			.once()
			.reply( 200, { status: 'ok' } );

		const result = await httpClient( {
			restRoute: '/sensei-internal/v1/some-internal-api',
			method: 'post',
			data: { foo: 'bar' },
		} );

		expect( result ).toEqual( { status: 'ok' } );

		expect( nock.isDone() ).toBeTruthy();
	} );
} );
