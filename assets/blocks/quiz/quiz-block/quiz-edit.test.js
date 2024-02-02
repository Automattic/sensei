/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import { Fill, SlotFillProvider } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import QuizEdit from './quiz-edit';
import { useQuizStructure } from '../quiz-store';
import QuizSettings from './quiz-settings';
import QuizValidationResult from './quiz-validation';

jest.mock( '@wordpress/data' );
jest.mock( './use-update-quiz-has-questions-meta' );
jest.mock( './quiz-validation' );
jest.mock( '../quiz-store' );
jest.mock( './quiz-settings' );
jest.mock( '@wordpress/edit-post', () => ( {} ) );

jest.mock( '../../../shared/blocks/use-auto-inserter', () => ( {
	useAutoInserter: jest.fn(),
} ) );

jest.mock( '@wordpress/block-editor', () => ( {
	...jest.requireActual( '@wordpress/block-editor' ),
	useBlockProps: jest.fn().mockImplementation( () => ( {
		className: 'test',
	} ) ),
} ) );

describe( 'addQuestionGeneratorUpsellButtonToQuizBlock', () => {
	beforeAll( () => {
		useSelect.mockReturnValue( {} );
		useDispatch.mockReturnValue( {} );
		QuizSettings.mockReturnValue( <div /> );
		QuizValidationResult.mockReturnValue( <div /> );
		useQuizStructure.mockReturnValue( {} );
	} );

	it( 'Should have slot for header', async () => {
		const { getByText } = render(
			<SlotFillProvider>
				<Fill name="SenseiQuizHeader">
					<div>Quiz Header Filled</div>
				</Fill>
				<QuizEdit attributes={ {} } />
			</SlotFillProvider>
		);

		expect( getByText( 'Quiz Header Filled' ) ).toBeInTheDocument();
	} );

	it( 'Should have slot for loader', async () => {
		const { getByText } = render(
			<SlotFillProvider>
				<Fill name="SenseiQuizBlockTop">
					<div>Quiz Loader Filled</div>
				</Fill>
				<QuizEdit attributes={ {} } />
			</SlotFillProvider>
		);

		expect( getByText( 'Quiz Loader Filled' ) ).toBeInTheDocument();
	} );
} );
