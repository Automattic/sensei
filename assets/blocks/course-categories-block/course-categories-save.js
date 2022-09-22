/**
 * Internal dependencies
 */
import useCourseCategoriesProps from './hooks/use-course-categories-props';

const CourseCategoriesSave = ( { attributes } ) => {
	const blockProps = useCourseCategoriesProps( attributes );

	return <div { ...blockProps }></div>;
};

export default CourseCategoriesSave;
