/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import edit from './learner-courses-edit';
import metadata from './block.json';
import icon from '../../icons/learner-courses.svg';

export default {
	title: __( 'Student Courses', 'sensei-lms' ),
	description: __(
		'Manage what students see on their dashboard. This block is only displayed to logged in students.',
		'sensei-lms'
	),
	keywords: [
		__( 'Student Courses', 'sensei-lms' ),
		__( 'My Courses', 'sensei-lms' ),
		__( 'Dashboard', 'sensei-lms' ),
		__( 'Courses', 'sensei-lms' ),
		__( 'Enrolled', 'sensei-lms' ),
		__( 'Learner', 'sensei-lms' ),
		__( 'Student', 'sensei-lms' ),
	],
	example: {},
	icon,
	edit,
	...metadata,
};
