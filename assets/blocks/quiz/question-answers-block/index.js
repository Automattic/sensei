/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

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
	title: __( 'Answers', 'sensei-lms' ),
	icon,
	description: __( 'Question Answers.', 'sensei-lms' ),
	edit,
	save: () => <InnerBlocks.Content />,
};
