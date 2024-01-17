/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';
import '@testing-library/jest-dom';

/**
 * WordPress dependencies
 */
import { useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import CourseDetailsStep from './course-details-step';

jest.mock( '@wordpress/data' );
jest.mock( '@wordpress/editor' );

const ANY_PLUGIN_URL = 'https://some-url/';

describe( '<CourseDetailsStep />', () => {
	beforeAll( () => {
		// Mock `window.sensei.pluginUrl`.
		Object.defineProperty( window, 'sensei', {
			value: {
				pluginUrl: ANY_PLUGIN_URL,
			},
		} );
	} );
	it( 'Renders both title and description input fields and not calls savePost initially.', () => {
		const editPostMock = jest.fn();
		useDispatch.mockReturnValue( { editPost: editPostMock } );

		const { queryByLabelText } = render(
			<CourseDetailsStep wizardData={ {} } setWizardData={ () => {} } />
		);

		expect( queryByLabelText( 'Course Title' ) ).toBeTruthy();
		expect( queryByLabelText( 'Course Description' ) ).toBeTruthy();
		expect( editPostMock ).toBeCalledTimes( 0 );
	} );

	it( 'Updates course title in data and as post title when changed.', () => {
		const editPostMock = jest.fn();
		const setDataMock = jest.fn();
		const NEW_TITLE = 'Some new title';
		useDispatch.mockReturnValue( { editPost: editPostMock } );

		const { queryByLabelText } = render(
			<CourseDetailsStep
				wizardData={ {} }
				setWizardData={ setDataMock }
			/>
		);
		fireEvent.change( queryByLabelText( 'Course Title' ), {
			target: { value: NEW_TITLE },
		} );

		expect( editPostMock ).toBeCalledWith( { title: NEW_TITLE } );
		expect( setDataMock ).toBeCalledWith( { title: NEW_TITLE } );
	} );

	it( 'Updates course description in data and as post excerpt when changed.', () => {
		const editPostMock = jest.fn();
		const setDataMock = jest.fn();
		const NEW_DESCRIPTION = 'Some new description';
		useDispatch.mockReturnValue( { editPost: editPostMock } );

		const { queryByLabelText } = render(
			<CourseDetailsStep
				wizardData={ {} }
				setWizardData={ setDataMock }
			/>
		);
		fireEvent.change( queryByLabelText( 'Course Description' ), {
			target: { value: NEW_DESCRIPTION },
		} );

		expect( editPostMock ).toBeCalledWith( { excerpt: NEW_DESCRIPTION } );
		expect( setDataMock ).toBeCalledWith( {
			description: NEW_DESCRIPTION,
		} );
	} );

	it( 'Focuses the Course Title input.', () => {
		const { queryByLabelText } = render(
			<CourseDetailsStep wizardData={ {} } setWizardData={ () => {} } />
		);

		expect( queryByLabelText( 'Course Title' ) ).toHaveFocus();
	} );
} );

describe( '<CourseDetailsStep.Actions />', () => {
	it( 'Does not call `goToNextStep` when rendering.', () => {
		const goToNextStepMock = jest.fn();

		render(
			<CourseDetailsStep.Actions goToNextStep={ goToNextStepMock } />
		);
		expect( goToNextStepMock ).toBeCalledTimes( 0 );
	} );

	it( 'Calls `goToNextStep` on click.', () => {
		const goToNextStepMock = jest.fn();

		const { queryByRole } = render(
			<CourseDetailsStep.Actions goToNextStep={ goToNextStepMock } />
		);
		fireEvent.click( queryByRole( 'button' ) );
		expect( goToNextStepMock ).toBeCalledTimes( 1 );
	} );
} );
