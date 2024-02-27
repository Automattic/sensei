/**
 * WordPress dependencies
 */
import { dispatch, select } from '@wordpress/data';
/**
 * Internal dependencies
 */
import { SENSEI_TOUR_STORE } from './store'; // Replace 'your-file-path' with the correct path to your file

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
} );
