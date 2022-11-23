/**
 * External dependencies
 */
import { isEqual } from 'lodash';

/**
 * WordPress dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { extractStructure, syncStructureToBlocks } from './data';

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
		( structure, updateSelection = false ) => {
			const blocks = getBlocks( clientId );
			const currentStructure = extractStructure( blocks );
			if ( ! isEqual( currentStructure, structure ) ) {
				replaceInnerBlocks(
					clientId,
					syncStructureToBlocks( structure, blocks ),
					updateSelection
				);
			}
		},
		[ clientId, replaceInnerBlocks, getBlocks ]
	);

	return { setBlocks };
};
