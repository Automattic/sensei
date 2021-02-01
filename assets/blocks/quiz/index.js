/**
 * Internal dependencies
 */
import registerSenseiBlocks from '../register-sensei-blocks';

import questionBlock from './question-block';
import quizBlock from './quiz-block';

registerSenseiBlocks( [ quizBlock, questionBlock ] );
