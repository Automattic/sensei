/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import icon from '../../../icons/quiz.svg';
import edit from './quiz-edit';
import metadata from './block.json';

/**
 * Quiz block definition.
 */
const quizBlock = {
	...metadata,
	metadata,
	icon,
	providesContext: {
		'sensei-lms/quizId': 'id',
	},
	edit,
	save: () => <InnerBlocks.Content />,
};

export default quizBlock;
