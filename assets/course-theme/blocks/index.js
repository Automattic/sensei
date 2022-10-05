/**
 * Internal dependencies
 */
import { registerTemplateBlocks } from './register-template-blocks';
import courseNavigationBlock from './course-navigation';
import uiBlocks from './ui';
import lessonBlocks from './lesson-blocks';
import quizBlocks from './quiz-blocks';
import { templateStyleBlock } from './template-style';

const blocks = [
	...lessonBlocks,
	...quizBlocks,
	...uiBlocks,
	courseNavigationBlock,
	templateStyleBlock,
];

registerTemplateBlocks( blocks );
