import { dispatch, useDispatch, useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { extractStructure, syncStructureToBlocks } from './data';
import { isEqual } from 'lodash';
import { COURSE_STORE } from './store';

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

				dispatch( COURSE_STORE ).setEditorDirty( true );
			}

			// Flag that the editor is sync.
			dispatch( COURSE_STORE ).setEditorSyncing( false );
		},
		[ clientId, replaceInnerBlocks, getBlocks ]
	);

	return { setBlocks };
};
