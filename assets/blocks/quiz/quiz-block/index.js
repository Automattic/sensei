/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import edit from './quiz-edit';
import metadata from './block.json';

/**
 * Quiz block definition.
 */
const quizBlock = {
	...metadata,
	title: __( 'Quiz', 'sensei-lms' ),
	description: __(
		'A collection of questions students need to answer.',
		'sensei-lms'
	),
	keywords: [
		__( 'Exam', 'sensei-lms' ),
		__( 'Questions', 'sensei-lms' ),
		__( 'Test', 'sensei-lms' ),
		__( 'Assessment', 'sensei-lms' ),
		__( 'Evaluation', 'sensei-lms' ),
	],
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
