/**
 * Internal dependencies
 */
import registerSenseiBlocks from '../register-sensei-blocks';

import answerFeedbackCorrectBlock from './answer-feedback-correct-block';
import answerFeedbackFailedBlock from './answer-feedback-failed-block';
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
	answerFeedbackFailedBlock,
	questionAnswersBlock,
];

registerSenseiBlocks( blocks );
