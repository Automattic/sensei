import { __ } from '@wordpress/i18n';
import edit from './edit';
import metadata from './block';

export default {
	title: __( 'Course Progress', 'sensei-lms' ),
	description: __(
		"Add a progress bar which displays the learner's course progress. The block is displayed only when the user is enrolled to the course.",
		'sensei-lms'
	),
	keywords: [
		__( 'Progress', 'sensei-lms' ),
		__( 'Bar', 'sensei-lms' ),
		__( 'Course', 'sensei-lms' ),
	],
	...metadata,
	edit,
};
