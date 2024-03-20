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
	...metadata,
	metadata,
	title: __( 'Course Actions', 'sensei-lms' ),
	description: __(
		'Enable a student to perform specific actions for a course.',
		'sensei-lms'
	),
	keywords: [
		__( 'course', 'sensei-lms' ),
		__( 'actions', 'sensei-lms' ),
		__( 'buttons', 'sensei-lms' ),
		__( 'start course', 'sensei-lms' ),
		__( 'continue', 'sensei-lms' ),
		__( 'visit results', 'sensei-lms' ),
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
	icon,
	edit,
	save,
};
