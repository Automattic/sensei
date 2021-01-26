/**
 * WordPress dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ACTION_BLOCKS, PREVIEW_STATE } from './constants';

/**
 * Preview state hook.
 *
 * @param {string} defaultPreviewState Default preview state.
 *
 * @return {Array} Array containing preview state and change handler, respectively.
 */
const usePreviewState = ( defaultPreviewState ) => {
	const [ previewState, setPreviewState ] = useState( defaultPreviewState );

	const selectedBlock = useSelect( ( select ) =>
		select( 'core/block-editor' ).getSelectedBlock()
	);

	// Update the preview state based on the block selection.
	useEffect( () => {
		if ( ! ACTION_BLOCKS.includes( selectedBlock?.name ) ) {
			return;
		}

		const newPreviewState = Object.keys( PREVIEW_STATE ).find( ( key ) =>
			PREVIEW_STATE[ key ].includes( selectedBlock.name )
		);

		if ( newPreviewState !== previewState ) {
			setPreviewState( newPreviewState );
		}
	}, [ selectedBlock, previewState ] );

	return [ previewState, setPreviewState ];
};

export default usePreviewState;
