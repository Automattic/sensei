/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

const CourseStatusOptions = [
	{
		label: __( 'Not Started', 'sensei-lms' ),
		value: 'not-started',
		showBlock: 'sensei-lms/button-take-course',
	},
	{
		label: __( 'In Progress', 'sensei-lms' ),
		value: 'in-progress',
		showBlock: 'sensei-lms/button-continue-course',
	},
	{
		label: __( 'Completed', 'sensei-lms' ),
		value: 'completed',
		showBlock: 'sensei-lms/button-view-results',
	},
];

export default CourseStatusOptions;
