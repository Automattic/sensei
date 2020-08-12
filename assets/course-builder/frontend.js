import { render, useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

import Lesson from './lesson';
import '../shared/data/api-fetch-preloaded-once';

const element = document.getElementById( 'course-outline-block' );

const CourseOutlineFrontend = () => {
	const [ lessons, setLessons ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	useEffect( () => {
		setLoading( true );
		apiFetch( {
			path: `/sensei-internal/v1/course-builder/course-lessons/${ element.dataset.id }`,
		} ).then( ( l ) => {
			setLessons( l );
			setLoading( false );
		} );
	}, [] );

	if ( loading ) {
		return 'Loading API...';
	}

	return (
		<div>
			<h1>Course outline</h1>
			{ lessons.map( ( lesson ) => (
				<Lesson key={ lesson.id } href={ lesson.permalink }>
					{ lesson.title }
				</Lesson>
			) ) }
		</div>
	);
};

if ( element ) {
	render( <CourseOutlineFrontend />, element );
}
