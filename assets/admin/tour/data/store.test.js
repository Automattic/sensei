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
	beforeEach( () => {
		apiFetch.mockClear();
	} );

	it( 'should set the tour show value properly', () => {
		dispatch( SENSEI_TOUR_STORE ).setTourShowStatus(
			false,
			false,
			'test-tour'
		);
		const showTour = select( SENSEI_TOUR_STORE ).shouldShowTour();
		dispatch( SENSEI_TOUR_STORE ).setTourShowStatus(
			true,
			true,
			'test-tour'
		);
		const showTourAfter = select( SENSEI_TOUR_STORE ).shouldShowTour();

		expect( showTour ).toBe( false );
		expect( showTourAfter ).toBe( true );
	} );

	it( 'should call API fetch when persistOnServer is true', () => {
		apiFetch.mockReturnValue( {} );

		dispatch( SENSEI_TOUR_STORE ).setTourShowStatus(
			false,
			true,
			'test-tour'
		);

		expect( apiFetch ).toHaveBeenCalledWith( {
			data: { complete: true, tour_id: 'test-tour' },
			method: 'POST',
			path: 'sensei-internal/v1/tour',
		} );
	} );

	it( 'should not call API fetch when persistOnServer is false', () => {
		apiFetch.mockReturnValue( {} );

		dispatch( SENSEI_TOUR_STORE ).setTourShowStatus(
			false,
			false,
			'test-tour'
		);

		expect( apiFetch ).not.toHaveBeenCalled();
	} );

	it( 'should API fetch with complete = false when show and persistOnServer is true', () => {
		apiFetch.mockReturnValue( {} );

		dispatch( SENSEI_TOUR_STORE ).setTourShowStatus(
			true,
			true,
			'test-tour'
		);

		expect( apiFetch ).toHaveBeenCalledWith( {
			data: { complete: false, tour_id: 'test-tour' },
			method: 'POST',
			path: 'sensei-internal/v1/tour',
		} );
	} );

	it( 'should not call API fetch but set local value as expected when persistOnServer param not set', () => {
		apiFetch.mockReturnValue( {} );

		dispatch( SENSEI_TOUR_STORE ).setTourShowStatus( false );
		const showTour = select( SENSEI_TOUR_STORE ).shouldShowTour();

		dispatch( SENSEI_TOUR_STORE ).setTourShowStatus( true );
		const showTourAfter = select( SENSEI_TOUR_STORE ).shouldShowTour();

		expect( showTour ).toBe( false );
		expect( showTourAfter ).toBe( true );
		expect( apiFetch ).not.toHaveBeenCalled();
	} );
} );
