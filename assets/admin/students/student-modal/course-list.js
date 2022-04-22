/**
 * WordPress dependencies
 */
import { CheckboxControl, Spinner } from '@wordpress/components';
import { useEffect, useState, useCallback, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

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
 * Course item.
 *
 * @param {Object}        props
 * @param {Object}        props.course   Course
 * @param {boolean}       props.checked  Checkbox state
 * @param {onChangeEvent} props.onChange Event triggered when the a course is select/unselected
 */
const CourseItem = ( { course, checked, onChange } ) => {
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
				checked={ checked }
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
 * @param {onCourseSelectionChange} props.onChange Event triggered when a course is selected or unselected
 */
export const CourseList = ( { onChange } ) => {
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
	useEffect( () => {
		setIsFetching( true );

		httpClient( {
			path: '/wp/v2/courses?per_page=100',
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
		return () => ( isMounted.current = false );
	}, [] );

	if ( isFetching ) {
		return (
			<div className="sensei-student-modal__course-list--loading">
				<Spinner />
			</div>
		);
	}

	if ( 0 === courses.length ) {
		return <p>{ __( 'No courses found.', 'sensei-lms' ) }</p>;
	}

	return (
		<>
			<span className="sensei-student-modal__course-list__header">
				{ __( 'Your Courses', 'sensei-lms' ) }
			</span>
			<ul className="sensei-student-modal__course-list">
				{ courses.map( ( course ) => (
					<CourseItem
						key={ course.id }
						course={ course }
						onChange={ selectCourse }
						checked={ selectedCourses.current.find(
							( { id } ) => id === course.id
						) }
					/>
				) ) }
			</ul>
		</>
	);
};

export default CourseList;
