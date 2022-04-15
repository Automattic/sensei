/**
 * Internal dependencies
 */
import httpClient from '.';
/**
 * External dependencies
 */
import nock from 'nock';

describe( 'http-client', () => {
	it( 'Should make a request to a rest route', async () => {
		nock( 'http://localhost' )
			.get( '/' )
			.query( {
				rest_route: '/sensei-internal/v1/some-internal-api',
				foo: 'bar',
			} )
			.once()
			.reply( 200, 'A correct response' );

		const result = await httpClient( {
			restRoute: '/sensei-internal/v1/some-internal-api',
			method: 'GET',
			params: { foo: 'bar' },
		} );

		expect( result.data ).toEqual( 'A correct response' );

		expect( nock.isDone() ).toBeTruthy();
	} );

	it( 'Should get a nonce when the operation is different of GET', async () => {
		nock( 'http://localhost' )
			.get( '/wp-admin/admin-ajax.php' )
			.query( {
				action: 'rest-nonce',
			} )
			.once()
			.reply( 200, 'some-nonce-id' );

		nock( 'http://localhost' )
			.post( '/', { foo: 'bar' } )
			.query( {
				rest_route: '/sensei-internal/v1/some-internal-api',
				_wpnonce: 'some-nonce-id',
			} )
			.once()
			.reply( 200, 'A correct response' );

		const result = await httpClient( {
			restRoute: '/sensei-internal/v1/some-internal-api',
			method: 'POST',
			data: { foo: 'bar' },
		} );

		expect( result.data ).toEqual( 'A correct response' );

		expect( nock.isDone() ).toBeTruthy();
	} );
} );
