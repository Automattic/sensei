/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';
import '@testing-library/jest-dom';

/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import LessonDetailsStep from './lesson-details-step';

jest.mock( '@wordpress/data' );
jest.mock( '@wordpress/editor' );

const ANY_PLUGIN_URL = 'https://some-url/';
const ANY_LESSON_TITLE = 'Any lesson title.';

describe( '<LessonDetailsStep />', () => {
	beforeAll( () => {
		// Mock `window.sensei.pluginUrl`.
		Object.defineProperty( window, 'sensei', {
			value: {
				pluginUrl: ANY_PLUGIN_URL,
			},
		} );
	} );
	afterEach( () => {
		jest.resetAllMocks();
	} );
	it( 'Renders title input field and not calls savePost initially.', () => {
		const editPostMock = jest.fn();
		useDispatch.mockReturnValue( { editPost: editPostMock } );
		useSelect.mockReturnValue( { postTitle: ANY_LESSON_TITLE } );

		const { queryByLabelText } = render(
			<LessonDetailsStep wizardData={ {} } setWizardData={ () => {} } />
		);

		expect( queryByLabelText( 'Lesson Title' ) ).toBeTruthy();
		expect( editPostMock ).toBeCalledTimes( 0 );
	} );

	it( 'Updates lesson title in data and as title post when changed.', () => {
		const editPostMock = jest.fn();
		const setDataMock = jest.fn();
		const NEW_TITLE = 'Some new title';
		useDispatch.mockReturnValue( { editPost: editPostMock } );
		useSelect.mockReturnValue( { postTitle: ANY_LESSON_TITLE } );

		const { queryByLabelText } = render(
			<LessonDetailsStep
				wizardData={ {} }
				setWizardData={ setDataMock }
			/>
		);
		fireEvent.change( queryByLabelText( 'Lesson Title' ), {
			target: { value: NEW_TITLE },
		} );

		expect( editPostMock ).toBeCalledWith( { title: NEW_TITLE } );
		expect( setDataMock ).toBeCalledWith( { title: NEW_TITLE } );
	} );

	it( 'Renders post title in title field initially.', () => {
		const editPostMock = jest.fn();
		useDispatch.mockReturnValue( { editPost: editPostMock } );
		useSelect.mockReturnValue( { postTitle: ANY_LESSON_TITLE } );

		const { queryByLabelText } = render(
			<LessonDetailsStep wizardData={ {} } setWizardData={ () => {} } />
		);

		expect( queryByLabelText( 'Lesson Title' ) ).toBeTruthy();
		expect( queryByLabelText( 'Lesson Title' ) ).toHaveDisplayValue(
			ANY_LESSON_TITLE
		);
	} );

	it( 'Should call initial lesson content setter when available', () => {
		const wizardDataSetter = jest.fn();
		const initialContent = 'test content';
		useDispatch.mockReturnValue( { editPost: jest.fn() } );
		useSelect.mockReturnValue( {
			postTitle: ANY_LESSON_TITLE,
			_initial_content: initialContent,
		} );

		render(
			<LessonDetailsStep
				wizardData={ {} }
				setWizardData={ wizardDataSetter }
			/>
		);

		expect( wizardDataSetter ).toBeCalledWith( {
			description: initialContent,
		} );
	} );
} );

describe( '<LessonDetailsStep.Actions />', () => {
	it( 'Does not call `goToNextStep` when rendering.', () => {
		const goToNextStepMock = jest.fn();

		render(
			<LessonDetailsStep.Actions goToNextStep={ goToNextStepMock } />
		);
		expect( goToNextStepMock ).toBeCalledTimes( 0 );
	} );

	it( 'Calls `goToNextStep` on click.', () => {
		const goToNextStepMock = jest.fn();

		const { queryByRole } = render(
			<LessonDetailsStep.Actions goToNextStep={ goToNextStepMock } />
		);
		fireEvent.click( queryByRole( 'button' ) );
		expect( goToNextStepMock ).toBeCalledTimes( 1 );
	} );
} );
