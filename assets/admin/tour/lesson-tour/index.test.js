/**
 * Internal dependencies
 */
import { getFirstBlockByName } from '../../../blocks/course-outline/data';
import LessonTour from './index';
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

describe( 'LessonTour', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		select.mockReturnValue( {
			getBlocks: () => null,
		} );
	} );

	test( 'should render null when no quiz block', () => {
		// Mocking getOutlineBlock to return null.
		getFirstBlockByName.mockReturnValueOnce( null );

		const { queryByText } = render( <LessonTour /> );
		expect( queryByText( 'Tour Kit Output' ) ).toBeFalsy();
	} );

	test( 'should render SenseiTourKit when quiz block exists', () => {
		// Mocking getOutlineBlock to return true.
		getFirstBlockByName.mockReturnValueOnce( true );

		const { getAllByText } = render( <LessonTour /> );
		expect( getAllByText( 'Tour Kit Output' ) ).toBeTruthy();
	} );

	test( 'should pass the correct steps to inner block', () => {
		// Mocking getOutlineBlock to return true. Otherwise, the component will return null.
		getFirstBlockByName.mockReturnValueOnce( true );

		render( <LessonTour /> );

		const tourSteps = getTourSteps();
		expect( mockFunction.mock.calls[ 0 ][ 0 ].steps[ 5 ].slug ).toEqual(
			tourSteps[ 5 ].slug
		);
	} );
} );
