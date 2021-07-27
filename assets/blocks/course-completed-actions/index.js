import { __ } from '@wordpress/i18n';
import icon from '../../icons/buttons-icon';
import metadata from './block.json';
import edit from './edit';
import save from './save';

export default {
	title: __( 'Course Completed Actions', 'sensei-lms' ),
	description: __(
		'Prompt learners to take action after completing a course.',
		'sensei-lms'
	),
	keywords: [
		__( 'Course', 'sensei-lms' ),
		__( 'Completed', 'sensei-lms' ),
		__( 'Actions', 'sensei-lms' ),
		__( 'Buttons', 'sensei-lms' ),
	],
	example: {
		innerBlocks: [
			{
				name: 'core/button',
				attributes: { text: __( 'Find More Courses', 'sensei-lms' ) },
			},
		]
	},
	...metadata,
	icon,
	edit,
	save,
};
