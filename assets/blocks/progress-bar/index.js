import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import edit from './edit';

registerBlockType( 'sensei-lms/course-progress-bar', {
	title: __( 'Course Progress Bar', 'sensei-lms' ),
	description: __(
		"Add a bar which displays the learner's course progress. It displayed only when the user is enrolled to the course.",
		'sensei-lms'
	),
	category: 'sensei-lms',
	keywords: [
		__( 'Progress', 'sensei-lms' ),
		__( 'Bar', 'sensei-lms' ),
		__( 'Course', 'sensei-lms' ),
	],
	supports: {
		html: false,
	},
	edit,
} );
