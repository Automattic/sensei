/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './lesson-actions-edit';
import save from './lesson-actions-save';
import icon from '../../../icons/buttons.svg';

export default {
	...metadata,
	metadata,
	title: __( 'Lesson Actions', 'sensei-lms' ),
	description: __(
		'Enable a student to perform specific actions for a lesson.',
		'sensei-lms'
	),
	keywords: [
		__( 'lesson', 'sensei-lms' ),
		__( 'actions', 'sensei-lms' ),
		__( 'buttons', 'sensei-lms' ),
		__( 'complete', 'sensei-lms' ),
		__( 'next', 'sensei-lms' ),
		__( 'reset', 'sensei-lms' ),
	],
	example: {
		innerBlocks: [
			{ name: 'sensei-lms/button-lesson-completed' },
			{ name: 'sensei-lms/button-complete-lesson' },
			{ name: 'sensei-lms/button-next-lesson' },
			{ name: 'sensei-lms/button-reset-lesson' },
		],
	},
	icon,
	edit,
	save,
};
