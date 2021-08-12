/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import edit from './learner-courses-edit';
import metadata from './block.json';
import { LearnerCoursesIcon as icon } from '../../icons';

export default {
	title: __( 'Learner Courses', 'sensei-lms' ),
	description: __(
		'Manage what learners see on their dashboard. This block is only displayed to logged in learners.',
		'sensei-lms'
	),
	keywords: [
		__( 'Learner Courses', 'sensei-lms' ),
		__( 'My Courses', 'sensei-lms' ),
		__( 'Dashboard', 'sensei-lms' ),
		__( 'Courses', 'sensei-lms' ),
		__( 'Enrolled', 'sensei-lms' ),
		__( 'Student', 'sensei-lms' ),
		__( 'Learner', 'sensei-lms' ),
	],
	example: {},
	icon,
	edit,
	...metadata,
};
