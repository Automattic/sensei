/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import edit from './category-question-edit';
import metadata from './block.json';
import icon from '../../../icons/question.svg';

/**
 * Quiz category question block definition.
 */
export default {
	...metadata,
	metadata,
	title: __( 'Category Question', 'sensei-lms' ),
	description: __( 'Pull questions from a question category.', 'sensei-lms' ),
	icon,
	usesContext: [ 'sensei-lms/quizId' ],
	example: {
		attributes: {
			categoryName: __( 'Example Category', 'sensei-lms' ),
		},
	},
	edit,
	save: () => <InnerBlocks.Content />,
	messages: {
		noCategory: __( 'Assign a category to this question.', 'sensei-lms' ),
	},
};
