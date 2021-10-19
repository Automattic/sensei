/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import edit from './answer-feedback-failed';
import metadata from './block.json';
import icon from '../../../icons/answer-feedback-failed';

/**
 * Correct Answer Feedback block definition.
 */
export default {
	...metadata,
	title: __( 'Failed Answer Feedback', 'sensei-lms' ),
	icon,
	description: __( 'Display failed answer feedback.', 'sensei-lms' ),
	edit,
	save: () => <InnerBlocks.Content />,
};
