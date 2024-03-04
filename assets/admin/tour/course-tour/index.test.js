/**
 * Internal dependencies
 */
import { getFirstBlockByName } from '../../../blocks/course-outline/data';
import CourseTour from './index';
/**
 * External dependencies
 */
import { render } from '@testing-library/react';
/**
 * WordPress dependencies
 */
import { select } from '@wordpress/data';
import getTourSteps from './steps';

jest.mock( '../../../blocks/course-outline/data', () => ( {
	getFirstBlockByName: jest.fn(),
} ) );
jest.mock( '../components/sensei-tour-kit', () =>
	jest.fn().mockImplementation( ( props ) => {
		mockFunction( props );
		return <>Tour Kit Output</>;
	} )
);
jest.mock( '@wordpress/data' );

const mockFunction = jest.fn();

describe( 'CourseTour', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		select.mockReturnValue( {
			getBlocks: () => null,
		} );
	} );

	test( 'should render null when no outline block', () => {
		// Mocking getOutlineBlock to return null.
		getFirstBlockByName.mockReturnValueOnce( null );

		const { queryByText } = render( <CourseTour /> );
		expect( queryByText( 'Tour Kit Output' ) ).toBeFalsy();
	} );

	test( 'should render SenseiTourKit when outline block exists', () => {
		// Mocking getOutlineBlock to return true.
		getFirstBlockByName.mockReturnValueOnce( true );

		const { getAllByText } = render( <CourseTour /> );
		expect( getAllByText( 'Tour Kit Output' ) ).toBeTruthy();
	} );

	test( 'should pass the correct steps to inner block', () => {
		// Mocking getOutlineBlock to return true. Otherwise, the component will return null.
		getFirstBlockByName.mockReturnValueOnce( true );

		render( <CourseTour /> );

		const tourSteps = getTourSteps();
		expect( mockFunction.mock.calls[ 0 ][ 0 ].steps[ 5 ].slug ).toEqual(
			tourSteps[ 5 ].slug
		);
	} );

	test( 'should pass the tour id to inner block', () => {
		// Mocking getOutlineBlock to return true. Otherwise, the component will return null.
		getFirstBlockByName.mockReturnValueOnce( true );

		render( <CourseTour /> );

		expect( mockFunction.mock.calls[ 0 ][ 0 ].trackId ).toEqual(
			'course_outline_onboarding_step_complete'
		);
	} );
} );
