import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { extractStructure, syncStructureToBlocks } from './data';
import { isEqual } from 'lodash';

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
		( structure ) => {
			const blocks = getBlocks( clientId );
			const currentStructure = extractStructure( blocks );
			if ( ! isEqual( currentStructure, structure ) ) {
				replaceInnerBlocks(
					clientId,
					syncStructureToBlocks( structure, blocks ),
					false
				);
			}
		},
		[ clientId, replaceInnerBlocks, getBlocks ]
	);

	return { setBlocks };
};
