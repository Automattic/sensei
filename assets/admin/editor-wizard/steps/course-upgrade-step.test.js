/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import CourseUpgradeStep from './course-upgrade-step';

jest.mock( '@wordpress/data' );

const DUMMY_ROUNDED_PRICE_WITH_CENTS = '$130.00';
const DUMMY_ROUNDED_PRICE = '$130';
const DUMMY_PRICE_WITH_CENTS = '$123.50';
const DUMMY_PRICE_SUFFIX = ' USD';

describe( '<CourseUpgradeStep />', () => {
	beforeAll( () => {
		window.sensei = { pluginUrl: '' };
	} );

	it( 'Should return a component including the price rounded', () => {
		useSelect.mockReturnValue( {
			postType: 'course',
			senseiProExtension: {
				is_installed: false,
				price: DUMMY_ROUNDED_PRICE_WITH_CENTS,
			},
		} );
		const { queryByText } = render( <CourseUpgradeStep /> );
		expect(
			queryByText( DUMMY_ROUNDED_PRICE + DUMMY_PRICE_SUFFIX )
		).toBeTruthy();
	} );

	it( 'Should return a component including the price with cents', () => {
		useSelect.mockReturnValue( {
			postType: 'course',
			senseiProExtension: {
				is_installed: false,
				price: DUMMY_PRICE_WITH_CENTS,
			},
		} );
		const { queryByText } = render( <CourseUpgradeStep /> );
		expect(
			queryByText( DUMMY_PRICE_WITH_CENTS + DUMMY_PRICE_SUFFIX )
		).toBeTruthy();
	} );
} );
