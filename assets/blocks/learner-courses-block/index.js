/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import edit from './learner-courses-edit';
import metadata from './block';
import { LearnerCoursesIcon as icon } from '../../icons';

export default {
	title: __( 'Learner Courses', 'sensei-lms' ),
	description: __(
		'Manage what your learners will see on their dashboard once they enroll to your courses.',
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
	icon,
	edit,
	...metadata,
};
