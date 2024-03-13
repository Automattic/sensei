/**
 * Internal dependencies
 */
import LessonTour from './index';
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

describe( 'LessonTour', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useSelect.mockReturnValue( {
			quizBlock: true,
		} );
	} );

	test( 'should render null when no quiz block', () => {
		useSelect.mockReturnValue( {
			quizBlock: null,
		} );

		const { queryByText } = render( <LessonTour /> );
		expect( queryByText( 'Tour Kit Output' ) ).toBeFalsy();
	} );

	test( 'should render SenseiTourKit when quiz block exists', () => {
		const { getAllByText } = render( <LessonTour /> );
		expect( getAllByText( 'Tour Kit Output' ) ).toBeTruthy();
	} );

	test( 'should pass the correct steps to inner block', () => {
		render( <LessonTour /> );

		const tourSteps = getTourSteps();
		expect( mockFunction.mock.calls[ 0 ][ 0 ].steps[ 5 ].slug ).toEqual(
			tourSteps[ 5 ].slug
		);
	} );

	test( 'should pass the tour id to inner block', () => {
		render( <LessonTour /> );

		expect( mockFunction.mock.calls[ 0 ][ 0 ].trackId ).toEqual(
			'lesson_quiz_onboarding_step_complete'
		);
	} );
} );
