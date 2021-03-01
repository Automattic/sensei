/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import edit from './question-edit';
import metadata from './block.json';
import icon from '../../../icons/question-icon';
import { createQuestionBlockAttributes } from './question-block-attributes';

/**
 * Quiz question block definition.
 */
export default {
	...metadata,
	title: __( 'Question', 'sensei-lms' ),
	icon,
	deprecated: [
		{
			attributes: {
				grade: { type: 'number' },
				type: { type: 'string' },
				title: { type: 'string' },
				id: { type: 'number' },
				categories: { type: 'array' },
				shared: { type: 'boolean' },
				answer_feedback: { type: 'string' },
				teacher_notes: { type: 'string' },
				before: { type: 'string' },
				after: { type: 'string' },
				gap: { type: 'array' },
				options: { type: 'array' },
				random_order: { type: 'boolean' },
				answer: { type: 'boolean' },
			},
			isEligible: ( attr ) => {
				return null !== attr.grade;
			},
			migrate: ( attr ) => {
				return createQuestionBlockAttributes( attr );
			},
			save: () => {
				return <InnerBlocks.Content />;
			},
		},
	],
	usesContext: [ 'sensei-lms/quizId' ],
	description: __( 'The building block of all quizzes.', 'sensei-lms' ),
	example: {
		attributes: { title: __( 'Example Quiz Question', 'sensei-lms' ) },
	},
	edit,
	save: () => <InnerBlocks.Content />,
};
