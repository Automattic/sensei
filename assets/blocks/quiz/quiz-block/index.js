/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import icon from '../../../icons/quiz-icon';
import edit from './quiz-edit';
import metadata from './block.json';

/**
 * Quiz block definition.
 */
const quizBlock = {
	...metadata,
	title: __( 'Quiz', 'sensei-lms' ),
	icon,
	description: __(
		'Evaluate progress and strengthen understanding of course concepts.',
		'sensei-lms'
	),
	keywords: [
		__( 'Exam', 'sensei-lms' ),
		__( 'Questions', 'sensei-lms' ),
		__( 'Test', 'sensei-lms' ),
		__( 'Assessment', 'sensei-lms' ),
		__( 'Evaluation', 'sensei-lms' ),
	],
	providesContext: {
		'sensei-lms/quizId': 'id',
	},
	example: {
		innerBlocks: [
			{
				name: 'sensei-lms/quiz-question',
				attributes: {
					title: __( 'First Example Question', 'sensei-lms' ),
				},
			},
			{
				name: 'sensei-lms/quiz-question',
				attributes: {
					title: __( 'Second Example Question', 'sensei-lms' ),
				},
			},
		],
	},
	edit,
	save: () => <InnerBlocks.Content />,
};

export default quizBlock;
