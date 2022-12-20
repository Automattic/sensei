/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

const CourseStatusOptions = [
	{
		label: __( 'Enrolled', 'sensei-lms' ),
		value: 'enrolled',
	},
	{
		label: __( 'In Progress', 'sensei-lms' ),
		value: 'in-progress',
	},
	{
		label: __( 'Completed', 'sensei-lms' ),
		value: 'completed',
	},
];

export default CourseStatusOptions;
