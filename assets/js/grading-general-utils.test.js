/**
 * Internal dependencies
 */
import { areMultipleChoiceAnswersCorrect } from './grading-general-utils';

describe( 'areMultipleChoiceAnswersCorrect', () => {
	it( 'returns false when no answer was given', () => {
		const userAnswer = '';
		const correctAnswer = 'Correct answer<br>';

		const result = areMultipleChoiceAnswersCorrect(
			userAnswer,
			correctAnswer
		);

		expect( result ).toBe( false );
	} );

	it( 'returns true when the answer is correct', () => {
		const userAnswer = 'Correct answer';
		const correctAnswer = 'Correct answer<br>';

		const result = areMultipleChoiceAnswersCorrect(
			userAnswer,
			correctAnswer
		);

		expect( result ).toBe( true );
	} );

	it( 'returns false when the answer is incorrect', () => {
		const userAnswer = 'Wrong answer';
		const correctAnswer = 'Correct answer<br>';

		const result = areMultipleChoiceAnswersCorrect(
			userAnswer,
			correctAnswer
		);

		expect( result ).toBe( false );
	} );

	it( 'returns true when all correct answers are selected', () => {
		const userAnswer = 'First answer<br>Second answer';
		const correctAnswer = 'First answer<br>Second answer<br>';

		const result = areMultipleChoiceAnswersCorrect(
			userAnswer,
			correctAnswer
		);

		expect( result ).toBe( true );
	} );
} );
