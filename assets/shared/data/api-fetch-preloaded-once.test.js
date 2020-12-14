import apiFetch from '@wordpress/api-fetch';
import { preloadedDataUsedOnceMiddleware } from './api-fetch-preloaded-once';

describe( 'preloadedDataUsedOnceMiddleware', () => {
	beforeEach( () => {
		apiFetch.use(
			apiFetch.createPreloadingMiddleware( {
				'/test': { body: 'preloaded-result' },
			} )
		);
		apiFetch.use( preloadedDataUsedOnceMiddleware() );
		window.fetch = jest.fn();
	} );

	it( 'loads cached data on the first request', async () => {
		const result = await apiFetch( {
			path: '/test',
		} );

		expect( result ).toEqual( 'preloaded-result' );
	} );

	it( 'loads fresh data on additional requests', async () => {
		window.fetch.mockReturnValue( Promise.reject( null ) );
		await apiFetch( { path: '/test' } );
		try {
			await apiFetch( { path: '/test' } );
		} catch ( err ) {}

		expect( window.fetch ).toHaveBeenCalledWith(
			expect.stringContaining( '/test' ),
			expect.anything()
		);
	} );
} );
