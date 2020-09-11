import { useDispatch } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { convertToBlocks } from './data';

/**
 * Blocks creator hook.
 * It adds blocks dynamically to the InnerBlock.
 *
 * @param {string} clientId Block client ID.
 */
export const useBlocksCreator = ( clientId ) => {
	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );

	const setBlocks = useCallback(
		( blockData ) =>
			replaceInnerBlocks( clientId, convertToBlocks( blockData ), false ),
		[ clientId, replaceInnerBlocks ]
	);

	return { setBlocks };
};
