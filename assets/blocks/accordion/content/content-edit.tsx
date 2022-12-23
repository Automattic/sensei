/**
 * WordPress dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

const TEMPLATE = [ [ 'core/paragraph', {} ] ];

/**
 * External dependencies
 */
export const Edit = () => {
	const blockProps = useBlockProps();

	const innerBlockProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
	} );

	return <div { ...innerBlockProps } />;
};

export default Edit;
