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
import icon from '../../../icons/buttons-icon';

export default {
	title: __( 'Lesson Actions', 'sensei-lms' ),
	description: __(
		'Enable a learner to perform specific actions for a lesson.',
		'sensei-lms'
	),
	keywords: [
		__( 'Lesson', 'sensei-lms' ),
		__( 'Actions', 'sensei-lms' ),
		__( 'Buttons', 'sensei-lms' ),
		__( 'Complete', 'sensei-lms' ),
		__( 'Next', 'sensei-lms' ),
		__( 'Reset', 'sensei-lms' ),
	],
	example: {
		innerBlocks: [
			{ name: 'sensei-lms/button-complete-lesson' },
			{ name: 'sensei-lms/button-next-lesson' },
			{ name: 'sensei-lms/button-reset-lesson' },
		],
	},
	...metadata,
	icon,
	edit,
	save,
};
