import { getParam, updateQueryString } from './url-functions';
import { mockSearch } from '../../tests-helper/functions';

describe( 'URL functions', () => {
	afterEach( () => {
		mockSearch( '' );
	} );

	describe( 'getParam', () => {
		it( 'Should return the URL param', () => {
			mockSearch( 'param=123&other-param=value' );
			const value = getParam( 'param' );

			expect( value ).toEqual( '123' );
		} );

		it( 'Should return null when there is no param', () => {
			const value = getParam( 'param' );

			expect( value ).toBeNull();
		} );

		it( 'Should return null when param is empty', () => {
			mockSearch( 'route=' );
			const value = getParam( 'param' );

			expect( value ).toBeNull();
		} );
	} );

	describe( 'updateQueryString', () => {
		it( 'Should add a param to the current URL', () => {
			const pushStateSpy = jest.spyOn( window.history, 'pushState' );

			updateQueryString( 'route', 'test-route' );

			expect( pushStateSpy ).toHaveBeenCalledWith(
				{},
				'',
				'?route=test-route'
			);
		} );

		it( 'Should change the param if it already exists', () => {
			mockSearch( 'route=pre-existing&other-param=value' );

			const pushStateSpy = jest.spyOn( window.history, 'pushState' );

			updateQueryString( 'route', 'test-route' );

			expect( pushStateSpy ).toHaveBeenCalledWith(
				{},
				'',
				'?route=test-route&other-param=value'
			);
		} );

		it( 'Should update the param with the replaceState when flag is true', () => {
			const replaceStateSpy = jest.spyOn(
				window.history,
				'replaceState'
			);

			updateQueryString( 'route', 'test-route', true );

			expect( replaceStateSpy ).toHaveBeenCalledWith(
				{},
				'',
				'?route=test-route'
			);
		} );

		it( 'Should remove param from query string', () => {
			mockSearch( 'param=123' );
			const pushStateSpy = jest.spyOn( window.history, 'pushState' );

			updateQueryString( 'param', null );

			expect( pushStateSpy ).toHaveBeenCalledWith( {}, '', '?' );
		} );
	} );
} );
