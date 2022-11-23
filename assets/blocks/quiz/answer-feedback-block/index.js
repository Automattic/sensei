/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import edit from './answer-feedback';
import icon from '../../../icons/question.svg';

const sharedMetadata = {
	parent: [ 'sensei-lms/quiz-question' ],
	category: 'sensei-lms',
	supports: {
		html: false,
	},
	attributes: {
		id: {
			type: 'integer',
		},
	},
};

/**
 * Correct Answer Feedback block definition.
 */
export const answerFeedbackCorrectBlock = {
	...sharedMetadata,
	name: 'sensei-lms/quiz-question-feedback-correct',
	title: __( 'Correct Answer Feedback', 'sensei-lms' ),
	icon,
	description: __( 'Display correct answer feedback.', 'sensei-lms' ),
	edit: ( props ) => edit( { ...props, type: 'correct' } ),
	save: () => <InnerBlocks.Content />,
};

/**
 * Incorrect Answer Feedback block definition.
 */
export const answerFeedbackIncorrectBlock = {
	...sharedMetadata,
	name: 'sensei-lms/quiz-question-feedback-incorrect',
	title: __( 'Incorrect Answer Feedback', 'sensei-lms' ),
	icon,
	description: __( 'Display incorrect answer feedback.', 'sensei-lms' ),
	edit: ( props ) => edit( { ...props, type: 'incorrect' } ),
	save: () => <InnerBlocks.Content />,
};
