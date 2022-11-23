/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import edit from './question-answers';
import metadata from './block.json';
import icon from '../../../icons/question.svg';

/**
 * Question answers block.
 */
export default {
	...metadata,
	metadata,
	icon,
	edit,
	save: () => <InnerBlocks.Content />,
};
