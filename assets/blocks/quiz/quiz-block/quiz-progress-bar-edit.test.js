/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import QuizProgressBarEdit from './quiz-progress-bar-edit';
import ProgressBar from '../../../shared/blocks/progress-bar';

jest.mock( '@wordpress/block-editor', () => ( {
	useBlockProps: jest.fn(),
} ) );

describe( 'Testing Quiz Progress Bar Edit', () => {
	it( 'Test ProgressBar is rendered with expected properties', () => {
		const pagination = {
			progressBarColor: 'black',
			progressBarBackground: 'white',
			progressBarRadius: 6,
			progressBarHeight: 12,
		};
		const barAttributes = {
			style: {
				backgroundColor: 'black',
			},
		};

		const barWrapperAttributes = {
			style: {
				backgroundColor: 'white',
				height: 12,
				borderRadius: 6,
			},
		};

		const quizProgressBarEditTest = render(
			<QuizProgressBarEdit pagination={ pagination } />
		);
		const progressBar = render(
			<ProgressBar
				totalCount={ 10 }
				completedCount={ 2 }
				barAttributes={ barAttributes }
				barWrapperAttributes={ barWrapperAttributes }
			/>
		);
		expect( progressBar.baseElement ).toEqual(
			quizProgressBarEditTest.baseElement
		);
	} );
} );
