import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { syncStructureToBlocks } from './data';

/**
 * Blocks creator hook.
 * It adds blocks dynamically to the InnerBlock.
 *
 * @param {string} clientId Block client ID.
 */
export const useBlocksCreator = ( clientId ) => {
	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );

	const { getBlocks } = useSelect(
		( select ) => select( 'core/block-editor' ),
		[]
	);

	const setBlocks = useCallback(
		( blockData ) => {
			const blocks = getBlocks( clientId );
			replaceInnerBlocks(
				clientId,
				syncStructureToBlocks( blockData, blocks ),
				false
			);
		},
		[ clientId, replaceInnerBlocks, getBlocks ]
	);

	return { setBlocks };
};
