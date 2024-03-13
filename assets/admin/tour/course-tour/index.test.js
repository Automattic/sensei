/**
 * Internal dependencies
 */
import CourseTour from './index';
/**
 * External dependencies
 */
import { render } from '@testing-library/react';
/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
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
		useSelect.mockReturnValue( {
			courseOutlineBlock: true,
		} );
	} );

	test( 'should render null when no outline block', () => {
		// Mocking getOutlineBlock to return null.
		useSelect.mockReturnValueOnce( {
			courseOutlineBlock: null,
		} );

		const { queryByText } = render( <CourseTour /> );
		expect( queryByText( 'Tour Kit Output' ) ).toBeFalsy();
	} );

	test( 'should render SenseiTourKit when outline block exists', () => {
		const { getAllByText } = render( <CourseTour /> );
		expect( getAllByText( 'Tour Kit Output' ) ).toBeTruthy();
	} );

	test( 'should pass the correct steps to inner block', () => {
		render( <CourseTour /> );

		const tourSteps = getTourSteps();
		expect( mockFunction.mock.calls[ 0 ][ 0 ].steps[ 5 ].slug ).toEqual(
			tourSteps[ 5 ].slug
		);
	} );

	test( 'should pass the tour id to inner block', () => {
		render( <CourseTour /> );

		expect( mockFunction.mock.calls[ 0 ][ 0 ].trackId ).toEqual(
			'course_outline_onboarding_step_complete'
		);
	} );
} );
