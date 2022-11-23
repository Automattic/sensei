/**
 * Internal dependencies
 */
import registerSenseiBlocks from '../register-sensei-blocks';

import {
	answerFeedbackCorrectBlock,
	answerFeedbackIncorrectBlock,
} from './answer-feedback-block';
import questionDescriptionBlock from './question-description-block';
import questionAnswersBlock from './question-answers-block';
import questionBlock from './question-block';
import categoryQuestionBlock from './category-question-block';
import quizBlock from './quiz-block';
import './quiz-store';

const blocks = [
	quizBlock,
	questionBlock,
	categoryQuestionBlock,
	questionDescriptionBlock,
	answerFeedbackCorrectBlock,
	answerFeedbackIncorrectBlock,
	questionAnswersBlock,
];

registerSenseiBlocks( blocks );
