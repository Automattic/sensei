/**
 * WordPress dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import icon from '../../../icons/quiz.svg';
import edit from './quiz-edit';
import metadata from './block.json';

/**
 * Quiz block definition.
 */
const quizBlock = {
	...metadata,
	metadata,
	title: __( 'Quiz', 'sensei-lms' ),
	description: __(
		'Evaluate progress and strengthen understanding of course concepts.',
		'sensei-lms'
	),
	keywords: [
		__( 'exam', 'sensei-lms' ),
		__( 'questions', 'sensei-lms' ),
		__( 'test', 'sensei-lms' ),
		__( 'assessment', 'sensei-lms' ),
		__( 'evaluation', 'sensei-lms' ),
	],
	icon,
	providesContext: {
		'sensei-lms/quizId': 'id',
	},
	edit,
	save: () => <InnerBlocks.Content />,
};

export default quizBlock;
