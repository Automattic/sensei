/**
 * Internal dependencies
 */
import registerSenseiBlocks from '../register-sensei-blocks';

import questionBlock from './question-block';
import categoryQuestionBlock from './category-question-block';
import quizBlock from './quiz-block';
import './quiz-store';

const blocks = [ quizBlock, questionBlock ];

if ( window.sensei_quiz_blocks.category_question_enabled ) {
	blocks.push( categoryQuestionBlock );
}

registerSenseiBlocks( blocks );
