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
	...metadata,
	metadata,
	title: __( 'Student Courses', 'sensei-lms' ),
	description: __(
		'Manage what students see on their dashboard. This block is only displayed to logged in students.',
		'sensei-lms'
	),
	keywords: [
		__( 'student courses', 'sensei-lms' ),
		__( 'my courses', 'sensei-lms' ),
		__( 'dashboard', 'sensei-lms' ),
		__( 'courses', 'sensei-lms' ),
		__( 'enrolled', 'sensei-lms' ),
		__( 'learner', 'sensei-lms' ),
		__( 'student', 'sensei-lms' ),
	],
	example: {},
	icon,
	edit,
};
