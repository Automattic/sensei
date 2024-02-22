/**
 * Internal dependencies
 */
import SenseiTourKit from './index';
/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { WpcomTourKit } from '@automattic/tour-kit';
/**
 * WordPress dependencies
 */
import { when } from 'jest-when';
import getTourSteps from '../../course-tour/steps';

jest.mock( '@automattic/tour-kit', () => ( {
	WpcomTourKit: jest.fn(),
} ) );

const mockFunction = jest.fn();

describe( 'SenseiTourKit', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		when( WpcomTourKit ).mockImplementation( ( props ) => {
			mockFunction( props );
			return (
				<div>
					<h1>WpcomTourKit output</h1>
				</div>
			);
		} );
	} );

	test( 'should render wpcomtourkit as expected', () => {
		const steps = getTourSteps();

		const { queryByText } = render( <SenseiTourKit steps={ steps } /> );
		expect( queryByText( 'WpcomTourKit output' ) ).toBeTruthy();
	} );

	test( 'should pass the correct steps to wpcomtourkit', () => {
		const steps = getTourSteps();

		render( <SenseiTourKit steps={ steps } /> );

		expect(
			mockFunction.mock.calls[ 0 ][ 0 ].config.steps[ 5 ].slug
		).toEqual( steps[ 5 ].slug );
	} );
} );
