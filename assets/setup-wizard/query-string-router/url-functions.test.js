import { updateRouteURL, getCurrentRouteFromURL } from './url-functions';
import { mockSearch } from '../../tests-helper/functions';

describe( 'URL functions', () => {
	afterEach( () => {
		mockSearch( '' );
	} );

	describe( 'updateRouteURL', () => {
		it( 'Should add a param to the current URL', () => {
			const pushStateSpy = jest.spyOn( window.history, 'pushState' );

			updateRouteURL( 'route', 'test-route' );

			expect( pushStateSpy ).toHaveBeenCalledWith(
				{},
				'',
				'?route=test-route'
			);
		} );

		it( 'Should change the param if it already exists', () => {
			mockSearch( 'route=pre-existing&other-param=value' );

			const pushStateSpy = jest.spyOn( window.history, 'pushState' );

			updateRouteURL( 'route', 'test-route' );

			expect( pushStateSpy ).toHaveBeenCalledWith(
				{},
				'',
				'?route=test-route&other-param=value'
			);
		} );

		it( 'Should update the route with the replaceState when flag is true', () => {
			const replaceStateSpy = jest.spyOn(
				window.history,
				'replaceState'
			);

			updateRouteURL( 'route', 'test-route', true );

			expect( replaceStateSpy ).toHaveBeenCalledWith(
				{},
				'',
				'?route=test-route'
			);
		} );
	} );

	describe( 'getCurrentRouteFromURL', () => {
		it( 'Should return the current route key', () => {
			mockSearch( 'route=test-route&other-param=value' );
			const currentRoute = getCurrentRouteFromURL( 'route' );

			expect( currentRoute ).toEqual( 'test-route' );
		} );

		it( 'Should return empty key when there is no a route', () => {
			mockSearch( 'route=' );

			const currentRoute = getCurrentRouteFromURL( 'route' );

			expect( currentRoute ).toBeNull();
		} );
	} );
} );
