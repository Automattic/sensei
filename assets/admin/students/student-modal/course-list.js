/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { CheckboxControl, Spinner } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Course item.
 *
 * @param {Object} course Course.
 */
const CourseItem = ( course ) => {
	const courseId = course?.id;
	const title = course?.title?.rendered;

	return (
		<li
			className="sensei-student-modal__course-list__item"
			key={ courseId }
		>
			<CheckboxControl id={ `course-${ courseId }` } title={ title } />
			<label htmlFor={ `course-${ courseId }` } title={ title }>
				{ title }
			</label>
		</li>
	);
};

/**
 * Course list.
 */
export const CourseList = () => {
	const [ isFetching, setIsFetching ] = useState( true );
	const [ courses, setCourses ] = useState( [] );

	// Fetch the courses.
	useEffect( () => {
		let mounted = true;
		setIsFetching( true );

		apiFetch( {
			path: '/wp/v2/courses?per_page=100',
			method: 'GET',
		} )
			.then( ( result ) => {
				if ( mounted ) setCourses( result );
			} )
			.finally( () => {
				if ( mounted ) setIsFetching( false );
			} );
		return () => ( mounted = false );
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
				{ courses.map( CourseItem ) }
			</ul>
		</>
	);
};

export default CourseList;
