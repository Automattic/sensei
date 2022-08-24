/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './course-actions-edit';
import save from './course-actions-save';
import icon from '../../../icons/buttons.svg';

export default {
	title: __( 'Course Actions', 'sensei-lms' ),
	description: __(
		'Enable a student to perform specific actions for a course.',
		'sensei-lms'
	),
	keywords: [
		__( 'Course', 'sensei-lms' ),
		__( 'Actions', 'sensei-lms' ),
		__( 'Buttons', 'sensei-lms' ),
		__( 'Start Course', 'sensei-lms' ),
		__( 'Continue', 'sensei-lms' ),
		__( 'Visit Results', 'sensei-lms' ),
	],
	example: {
		innerBlocks: [
			{
				name: 'sensei-lms/button-take-course',
				attributes: {
					text: __( 'Start Course', 'sensei-lms' ),
				},
			},
		],
	},
	...metadata,
	icon,
	edit,
	save,
};
