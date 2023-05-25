/**
 * Internal dependencies
 */
import { registerTemplateBlocks } from './register-template-blocks';
import courseNavigationBlock from './course-navigation';
import uiBlocks from './ui';
import lessonBlocks from './lesson-blocks';
import quizBlocks from './quiz-blocks';
import senseiLogo from './sensei-logo-block';
import { templateStyleBlock } from './template-style';

const blocks = [
	...lessonBlocks,
	...quizBlocks,
	...uiBlocks,
	courseNavigationBlock,
	templateStyleBlock,
	senseiLogo,
];

registerTemplateBlocks( blocks );
