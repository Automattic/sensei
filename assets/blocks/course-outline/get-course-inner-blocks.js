import { select } from '@wordpress/data';

/**
 * Get the course outline inner blocks of a specific type.
 *
 * @param {string} outlineClientId The outline block client id.
 * @param {string} blockType       The block type to return.
 *
 * @return {Array} An array of blocks.
 */
export const getCourseInnerBlocks = ( outlineClientId, blockType ) => {
	let allChildren = select( 'core/block-editor' ).getBlocks(
		outlineClientId
	);

	allChildren = allChildren.reduce(
		( m, block ) => [ ...m, ...block.innerBlocks ],
		allChildren
	);

	return allChildren.filter( ( { name } ) => blockType === name );
};
