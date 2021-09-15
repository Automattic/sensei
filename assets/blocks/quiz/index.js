/**
 * Internal dependencies
 */
import registerSenseiBlocks from '../register-sensei-blocks';

import AnswerFeedbackCorrectBlock from './answer-feedback-correct-block';
import AnswerFeedbackFailedBlock from './answer-feedback-failed-block';
import QuestionDescriptionBlock from './question-description-block';
import questionBlock from './question-block';
import categoryQuestionBlock from './category-question-block';
import quizBlock from './quiz-block';
import './quiz-store';

const blocks = [
  quizBlock,
  questionBlock,
  categoryQuestionBlock,
  QuestionDescriptionBlock,
  AnswerFeedbackCorrectBlock,
  AnswerFeedbackFailedBlock,
];

registerSenseiBlocks( blocks );
