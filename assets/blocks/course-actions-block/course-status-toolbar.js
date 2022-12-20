/**
 * WordPress dependencies
 */
import { Toolbar } from '@wordpress/components';

/**
 * Internal dependencies
 */
import CourseStatusOptions from './course-status-options';
import ToolbarDropdown from '../editor-components/toolbar-dropdown';

/**
 * Toolbar component for the Course State. It can be Enrolled (the default), In
 * Progress, or Completed.
 *
 * @param {Object}   props
 * @param {string}   props.courseStatus    Course status.
 * @param {Function} props.setCourseStatus Function to set the course status.
 */
const CourseStatusToolbar = ( { courseStatus, setCourseStatus } ) => {
	return (
		<Toolbar>
			<ToolbarDropdown
				options={ CourseStatusOptions }
				optionsLabel="Course Status"
				value={ courseStatus }
				onChange={ setCourseStatus }
			/>
		</Toolbar>
	);
};

export default CourseStatusToolbar;
