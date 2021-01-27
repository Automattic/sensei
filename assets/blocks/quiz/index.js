import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { EditQuizBlock as edit } from './edit';
import questionBlock from './question-block';
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
		__( 'Quiz', 'sensei-lms' ),
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

export default [ quizBlock, questionBlock ];
