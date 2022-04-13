/**
 * WordPress dependencies
 */
import { CheckboxControl, Spinner } from '@wordpress/components';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * External dependencies
 */
import { debounce } from 'lodash';

/**
 * Internal dependencies
 */
import httpClient from '../../lib/http-client';

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
 * @param {onChangeEvent} props.onChange Event triggered when the a course is select/unselected
 */
const CourseItem = ( { course, onChange } ) => {
	const courseId = course?.id;
	const title = course?.title?.rendered;
	const onSelectCourse = useCallback(
		( isSelected ) => onChange( { isSelected, course } ),
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
 * @param {onCourseSelectionChange} props.onChange Event triggered when a course is selected or unselected
 */
export const CourseList = ( { searchQuery, onChange } ) => {
	const [ isFetching, setIsFetching ] = useState( true );
	const [ courses, setCourses ] = useState( [] );
	const selectedCourses = useRef( [] );
	const isMounted = useRef( true );

	const selectCourse = useCallback(
		( { isSelected, course } ) => {
			selectedCourses.current = isSelected
				? [ ...selectedCourses.current, course ]
				: selectedCourses.current.filter( ( c ) => c.id !== course.id );

			onChange( selectedCourses.current );
		},
		[ onChange ]
	);

	// Fetch the courses.
	const fetchCourses = useCallback(
		debounce( ( query ) => {
			setIsFetching( true );

			httpClient( {
				url: '/wp-json/wp/v2/courses?per_page=100' +
					( searchQuery ? `&search=${ searchQuery }` : '' ),
				method: 'GET',
			} )
				.then( ( result ) => {
					if ( isMounted.current ) {
						setCourses( result );
					}
				} )
				.catch( () => {
					if ( isMounted.current ) {
						setIsFetching( false );
					}
				} )
				.finally( () => {
					if ( isMounted.current ) {
						setIsFetching( false );
					}
				} );
		}, 400 ),
		[]
	);

	useEffect( () => {
		fetchCourses( searchQuery );

		return () => ( isMounted.current = false );
	}, [ fetchCourses, searchQuery ] );

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
						/>
					) )
				}
			</ul>
		</>
	);
};

export default CourseList;
