/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import edit from './question-edit';
import deprecated from './question-deprecated';
import metadata from './block.json';
import icon from '../../../icons/question.svg';

/**
 * Quiz question block definition.
 */
export default {
	...metadata,
	metadata,
	icon,
	usesContext: [ 'sensei-lms/quizId' ],
	deprecated,
	edit,
	save: () => <InnerBlocks.Content />,
	messages: {
		noTitle: __( 'Add a title to this question.', 'sensei-lms' ),
	},
};
