/**
 * External dependencies
 */
import { store as coreDataStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import useSelectWithDebounce from '../../react-hooks/use-select-with-debounce';

export default function CourseCategoryEdit( props ) {
	const { context } = props;

	const { postId } = context;
	const { courses, isFetching } = useSelectWithDebounce(
		( select ) => {
			const store = select( coreDataStore );

			const query = {
				ID: postId,
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
		[],
		500
	);
	if ( ! courses || courses.length === 0 ) {
		return <div></div>;
	}
	const course = courses.find( ( c ) => c.id === postId );
	const className =
		isFetching || ( course && course.meta._course_featured ) === 'featured'
			? 'class-course-featured'
			: '';
	return <div className={ className }></div>;
}
