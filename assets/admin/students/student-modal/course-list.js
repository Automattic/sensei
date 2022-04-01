/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Course list.
 */
const CourseList = () => {
	return (
		<ul className="sensei-course-list">
			<li className="sensei-course-list__item--empty">
				<p>{ __( 'No courses found.', 'sensei-lms' ) }</p>
			</li>
		</ul>
	);
};

export default CourseList;
