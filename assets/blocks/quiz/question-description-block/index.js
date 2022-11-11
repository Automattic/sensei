/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import edit from './question-description';
import metadata from './block.json';
import icon from '../../../icons/question.svg';

/**
 * Question description block.
 */
export default {
	...metadata,
	metadata,
	icon,
	usesContext: [ 'sensei-lms/quizId' ],
	edit,
	save: () => <InnerBlocks.Content />,
};
