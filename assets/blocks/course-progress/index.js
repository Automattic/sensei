import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import edit from './edit';
import metadata from './block';

registerBlockType( 'sensei-lms/course-progress', {
	title: __( 'Course Progress', 'sensei-lms' ),
	description: __(
		"Add a progress bar with a title which displays the learner's course progress. It displayed only when the user is enrolled to the course.",
		'sensei-lms'
	),
	keywords: [
		__( 'Progress', 'sensei-lms' ),
		__( 'Bar', 'sensei-lms' ),
		__( 'Course', 'sensei-lms' ),
	],
	...metadata,
	edit,
} );
