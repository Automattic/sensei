/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import edit from './answer-feedback-correct';
import metadata from './block.json';
import icon from '../../../icons/answer-feedback-correct';

/**
 * Correct Answer Feedback block definition.
 */
export default {
	...metadata,
	title: __( 'Correct Answer Feedback', 'sensei-lms' ),
	icon,
	description: __( 'Display correct answer feedback.', 'sensei-lms' ),
	edit,
	save: () => <InnerBlocks.Content />,
};
