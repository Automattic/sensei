/**
 * WordPress dependencies
 */
import { CheckboxControl, Spinner } from '@wordpress/components';
import { useCallback, useRef, useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';
import { store as coreDataStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import useSelectWithDebounce from '../../../react-hooks/use-select-with-debounce';

/**
 * Callback for select or unselect courseItem
 *
 * @callback onChangeEvent
 * @param {boolean} isSelected Describes if the course was selected or unselected
 * @param {boolean} course     Course related to the triggered event
 */

/**
 * Loading course list component.
 */
const LoadingCourseList = () => (
	<li className="sensei-student-modal__course-list--loading">
		<Spinner />
	</li>
);

/**
 * Empty course list component.
 */
const EmptyCourseList = () => (
	<li className="sensei-student-modal__course-list--empty">
		{ __( 'No courses found.', 'sensei-lms' ) }
	</li>
);

/**
 * Course item.
 *
 * @param {Object}        props
 * @param {Object}        props.course   Course
 * @param {boolean}       props.checked  Checkbox state
 * @param {onChangeEvent} props.onChange Event triggered when the a course is select/unselected
 */
const CourseItem = ( { course, checked = false, onChange } ) => {
	const courseId = course?.id;
	const title = decodeEntities( course?.title?.rendered );
	const [ isChecked, setIsChecked ] = useState( checked );

	const onSelectCourse = useCallback(
		( isSelected ) => {
			setIsChecked( isSelected );
			onChange( { isSelected, course } );
		},
		[ course, onChange ]
	);

	return (
		<li
			className="sensei-student-modal__course-list__item"
			key={ courseId }
		>
			<CheckboxControl
				id={ `course-${ courseId }` }
				title={ title }
				checked={ isChecked }
				onChange={ onSelectCourse }
			/>
			<label htmlFor={ `course-${ courseId }` } title={ title }>
				{ title }
			</label>
		</li>
	);
};

/**
 * Callback for CourseSelection
 *
 * @callback onCourseSelectionChange
 * @param {Array} selectedCourses List of selected courses
 */

/**
 * Course list.
 *
 * @param {Object}                  props
 * @param {string}                  props.searchQuery Course to search for.
 * @param {onCourseSelectionChange} props.onChange    Event triggered when a course is selected or unselected
 */
export const CourseList = ( { searchQuery, onChange } ) => {
	const selectedCourses = useRef( [] );

	const selectCourse = useCallback(
		( { isSelected, course } ) => {
			selectedCourses.current = isSelected
				? [ ...selectedCourses.current, course ]
				: selectedCourses.current.filter( ( c ) => c.id !== course.id );

			onChange( selectedCourses.current );
		},
		[ onChange ]
	);

	const { courses, isFetching } = useSelectWithDebounce(
		( select ) => {
			const store = select( coreDataStore );

			const query = {
				per_page: 100,
				search: searchQuery,
				filter: 'teacher',
			};

			return {
				courses:
					store.getEntityRecords( 'postType', 'course', query ) || [],
				isFetching: ! store.hasFinishedResolution( 'getEntityRecords', [
					'postType',
					'course',
					query,
				] ),
			};
		},
		[ searchQuery ],
		500
	);

	return (
		<>
			<span className="sensei-student-modal__course-list__header">
				{ __( 'Your Courses', 'sensei-lms' ) }
			</span>
			<ul className="sensei-student-modal__course-list">
				{ isFetching && <LoadingCourseList /> }

				{ ! isFetching && 0 === courses.length && <EmptyCourseList /> }

				{ ! isFetching &&
					0 < courses.length &&
					courses.map( ( course ) => (
						<CourseItem
							key={ course.id }
							course={ course }
							onChange={ selectCourse }
							checked={
								selectedCourses.current.length > 0 &&
								selectedCourses.current.find(
									( { id } ) => id === course.id
								)
							}
						/>
					) ) }
			</ul>
		</>
	);
};

export default CourseList;
