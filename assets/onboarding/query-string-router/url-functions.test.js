import { updateRouteURL, getCurrentRouteFromURL } from './url-functions';
import { mockSearch } from '../../tests-helper/functions';

describe( 'URL functions', () => {
	describe( 'updateRouteURL', () => {
		it( 'Should add a query string to the current URL', () => {
			const pushStateSpy = jest.spyOn( window.history, 'pushState' );

			updateRouteURL( 'route', 'test-route' );

			expect( pushStateSpy ).toHaveBeenCalledWith(
				{},
				'',
				'?route=test-route'
			);
		} );

		it( 'Should change the query string if it already exists', () => {
			mockSearch( 'route=pre-existing&other-param=value' );

			const pushStateSpy = jest.spyOn( window.history, 'pushState' );

			updateRouteURL( 'route', 'test-route' );

			expect( pushStateSpy ).toHaveBeenCalledWith(
				{},
				'',
				'?route=test-route&other-param=value'
			);
		} );
	} );

	describe( 'getCurrentRouteFromURL', () => {
		it( 'Should return the current route key', () => {
			mockSearch( 'route=test-route&other-param=value' );
			const currentRoute = getCurrentRouteFromURL( 'route' );

			expect( currentRoute ).toEqual( 'test-route' );
		} );

		it( 'Should return the first key as fallback when there is no a route', () => {
			mockSearch( 'route=' );

			const routes = [ { key: 'first-route' } ];
			const currentRoute = getCurrentRouteFromURL( 'route', routes );

			expect( currentRoute ).toEqual( 'first-route' );
		} );
	} );
} );
