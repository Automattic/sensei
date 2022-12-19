/**
 * WordPress dependencies
 */
import { Toolbar } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import ToolbarDropdown from '../editor-components/toolbar-dropdown';

const toolbarOptions = [
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

/**
 * Toolbar component for the Course State. It can be Enrolled (the default), In
 * Progress, or Completed.
 *
 * @param {Object}   props
 * @param {string}   props.courseStatus    Course status.
 * @param {Function} props.setCourseStatus Function to set the course status.
 */
const CourseStatusToolbar = ( { courseStatus, setCourseStatus } ) => {
	if ( ! courseStatus ) {
		courseStatus = toolbarOptions[ 0 ].value;
	}

	return (
		<Toolbar>
			<ToolbarDropdown
				options={ toolbarOptions }
				optionsLabel="Course Status"
				value={ courseStatus }
				onChange={ setCourseStatus }
			/>
		</Toolbar>
	);
};

export default CourseStatusToolbar;
