/**
 * WordPress dependencies
 */
import { Toolbar } from '@wordpress/components';
import { useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CourseStatusOptions from './course-status-options';
import CourseStatusContext from '../course-actions-block/course-status-context';
import ToolbarDropdown from '../editor-components/toolbar-dropdown';

/**
 * Toolbar component for the Course State. It can be Enrolled (the default), In
 * Progress, or Completed.
 *
 * @param {Object}   props
 * @param {string}   props.courseStatus           Course status.
 * @param {Function} props.setCourseStatus        Function to set the course status.
 * @param {boolean}  props.useCourseStatusContext Whether to use the course status context instead of callbacks.
 */
const CourseStatusToolbar = ( {
	courseStatus,
	setCourseStatus,
	useCourseStatusContext = false,
} ) => {
	const context = useContext( CourseStatusContext );

	// Render nothing if we should use the context but it is not available.
	if ( useCourseStatusContext && ! context?.courseStatus ) {
		return null;
	}

	const courseStatusValue = useCourseStatusContext
		? context.courseStatus
		: courseStatus;

	const setCourseStatusCallback = useCourseStatusContext
		? context.setCourseStatus
		: setCourseStatus;

	return (
		<Toolbar>
			<ToolbarDropdown
				options={ CourseStatusOptions }
				optionsLabel="Course Status"
				value={ courseStatusValue }
				onChange={ setCourseStatusCallback }
			/>
		</Toolbar>
	);
};

export default CourseStatusToolbar;
