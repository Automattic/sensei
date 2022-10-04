/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
/**
 * Internal dependencies
 */
import { getStyleAndClassesFromAttributes } from './utils/style';

const CourseCategoriesSave = ( { attributes } ) => {
	const blockProps = useBlockProps.save(
		getStyleAndClassesFromAttributes( attributes )
	);

	return <div { ...blockProps }></div>;
};

export default CourseCategoriesSave;
