/**
 * Internal dependencies
 */
import registerSenseiBlocks from '../register-sensei-blocks';

import questionBlock from './question-block';
import categoryQuestionBlock from './category-question-block';
import quizBlock from './quiz-block';
import './quiz-store';

const blocks = [ quizBlock, questionBlock, categoryQuestionBlock ];

registerSenseiBlocks( blocks );
