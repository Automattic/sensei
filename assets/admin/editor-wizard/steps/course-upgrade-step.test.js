/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';
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

const ANY_PLUGIN_URL = 'https://some-url/';

describe( '<CourseUpgradeStep />', () => {
	beforeAll( () => {
		// Mock `window.sensei.pluginUrl`.
		Object.defineProperty( window, 'sensei', {
			value: {
				pluginUrl: ANY_PLUGIN_URL,
			},
		} );
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

describe( '<CourseUpgradeStep.Actions />', () => {
	it( 'Does not call `goToNextStep` when rendering.', () => {
		const goToNextStepMock = jest.fn();

		render(
			<CourseUpgradeStep.Actions goToNextStep={ goToNextStepMock } />
		);
		expect( goToNextStepMock ).toBeCalledTimes( 0 );
	} );

	it( 'Calls `goToNextStep` on click.', () => {
		const goToNextStepMock = jest.fn();

		const { queryByText } = render(
			<CourseUpgradeStep.Actions goToNextStep={ goToNextStepMock } />
		);
		fireEvent.click( queryByText( 'Continue with Sensei Free' ) );
		expect( goToNextStepMock ).toBeCalledTimes( 1 );
	} );
} );
