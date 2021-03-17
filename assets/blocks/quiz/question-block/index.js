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
import icon from '../../../icons/question-icon';

/**
 * Quiz question block definition.
 */
export default {
	...metadata,
	title: __( 'Question', 'sensei-lms' ),
	icon,
	usesContext: [ 'sensei-lms/quizId' ],
	description: __( 'The building block of all quizzes.', 'sensei-lms' ),
	example: {
		attributes: { title: __( 'Example Quiz Question', 'sensei-lms' ) },
	},
	deprecated,
	edit,
	save: () => <InnerBlocks.Content />,
	messages: {
		noTitle: __( 'Add a title to this question.', 'sensei-lms' ),
	},
};
