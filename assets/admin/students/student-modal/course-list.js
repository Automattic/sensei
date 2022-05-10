/**
 * WordPress dependencies
 */
import { CheckboxControl, Spinner } from '@wordpress/components';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

/**
 * External dependencies
 */
import { debounce } from 'lodash';

/**
 * Internal dependencies
 */
import httpClient from '../../lib/http-client';
import useAbortController from '../hooks/use-abort-controller';

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
	const [ isFetching, setIsFetching ] = useState( true );
	const [ courses, setCourses ] = useState( [] );
	const selectedCourses = useRef( [] );
	const { getSignal } = useAbortController();

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
				path:
					'/wp/v2/courses?per_page=100' +
					( query ? `&search=${ query }` : '' ),
				method: 'GET',
				signal: getSignal(),
			} )
				.then( ( result ) => setCourses( result ) )
				.catch( ( error ) => {
					console.log( error ); // eslint-disable-line no-console
				} )
				.finally( () => {
					if ( ! getSignal().aborted ) {
						setIsFetching( false );
					}
				} );
		}, 400 ),
		[]
	); // eslint-disable-line react-hooks/exhaustive-deps

	useEffect( () => {
		fetchCourses( searchQuery );
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
