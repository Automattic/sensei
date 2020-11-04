import { __ } from '@wordpress/i18n';
import { ProgressIcon as icon } from '../../icons';
import edit from './edit';
import metadata from './block';

export default {
	title: __( 'Course Progress', 'sensei-lms' ),
	description: __(
		"Display the learner's progress in the course. This block is only visible if the learner is enrolled.",
		'sensei-lms'
	),
	keywords: [
		__( 'Progress', 'sensei-lms' ),
		__( 'Bar', 'sensei-lms' ),
		__( 'Course', 'sensei-lms' ),
	],
	icon,
	...metadata,
	edit,
};
