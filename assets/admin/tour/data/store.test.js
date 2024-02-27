/**
 * WordPress dependencies
 */
import { dispatch, select } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { SENSEI_TOUR_STORE } from './store';

jest.mock( '@wordpress/api-fetch' );

describe( 'Sensei Tour Store', () => {
	it( 'should set the tour show value properly', () => {
		dispatch( SENSEI_TOUR_STORE ).setTourShowStatus(
			false,
			true,
			'test-tour'
		);
		const showTour = select( SENSEI_TOUR_STORE ).getIfShowTour();
		dispatch( SENSEI_TOUR_STORE ).setTourShowStatus(
			true,
			true,
			'test-tour'
		);
		const showTourAfter = select( SENSEI_TOUR_STORE ).getIfShowTour();

		expect( showTour ).toBe( false );
		expect( showTourAfter ).toBe( true );
	} );

	it( 'should call API fetch when onlyLocal is false', () => {
		apiFetch.mockReturnValue( {} );

		dispatch( SENSEI_TOUR_STORE ).setTourShowStatus(
			false,
			false,
			'test-tour'
		);

		expect( apiFetch ).toHaveBeenCalledWith( {
			data: { complete: true, tour_id: 'test-tour' },
			method: 'POST',
			path: 'sensei-internal/v1/tour',
		} );
	} );
} );
