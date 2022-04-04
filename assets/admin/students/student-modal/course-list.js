/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { CheckboxControl, Spinner } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Course list.
 */
const CourseList = () => {
	const [ isFetching, setIsFetching ] = useState( true );
	const [ courses, setCourses ] = useState( [] );

	// Fetch the courses.
	useEffect( () => {
		setIsFetching( true );

		apiFetch( {
			path: '/wp/v2/courses?per_page=100',
			method: 'GET',
		} )
			.then( ( result ) => {
				setCourses( result );
			} )
			.finally( () => {
				setIsFetching( false );
			} );
	}, [] );

	if ( isFetching ) {
		return (
			<div className="sensei-course-list--loading">
				<Spinner />
			</div>
		);
	}

	if ( 0 === courses.length ) {
		return <p>{ __( 'No courses found.', 'sensei-lms' ) }</p>;
	}

	const coursesMap = ( course ) => {
		const courseId = course?.id;
		const title = course?.title?.rendered;

		return (
			<>
				<li className="sensei-course-list__item" key={ courseId }>
					<CheckboxControl
						id={ `course-${ courseId }` }
						title={ title }
					/>
					<label htmlFor={ `course-${ courseId }` } title={ title }>
						{ title }
					</label>
				</li>
			</>
		);
	};

	return (
		<>
			<ul className="sensei-course-list">
				{ courses.map( coursesMap ) }
			</ul>
		</>
	);
};

export default CourseList;
