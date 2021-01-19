import { useState, useEffect, useCallback } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';

import { ACTION_BLOCKS, PREVIEW_STATE } from './constants';

/**
 * Preview state hook.
 *
 * @param {Object} options                     Hook options.
 * @param {string} options.parentClientId      Parent client ID.
 * @param {string} options.defaultPreviewState Default preview state.
 *
 * @return {Array} Array containing preview state and change handler, respectively.
 */
const usePreviewState = ( { parentClientId, defaultPreviewState } ) => {
	const [ previewState, setPreviewState ] = useState( defaultPreviewState );
	const lessonActionsBlock = useSelect(
		( select ) => select( 'core/block-editor' ).getBlock( parentClientId ),
		[]
	);
	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );

	const selectedBlock = useSelect( ( select ) =>
		select( 'core/block-editor' ).getSelectedBlock()
	);

	/**
	 * Preview change handler.
	 *
	 * @param {string} newPreviewState New preview state.
	 */
	const onPreviewChange = useCallback(
		( newPreviewState ) => {
			const newBlocks = lessonActionsBlock.innerBlocks.map(
				( block ) => ( {
					...block,
					attributes: {
						...block.attributes,
						disabled: ! PREVIEW_STATE[ newPreviewState ].includes(
							block.name
						),
					},
				} )
			);

			replaceInnerBlocks( parentClientId, newBlocks, false );

			setPreviewState( newPreviewState );
		},
		[ parentClientId, lessonActionsBlock, replaceInnerBlocks ]
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
			onPreviewChange( newPreviewState );
		}
	}, [ selectedBlock, previewState, onPreviewChange ] );

	return [ previewState, onPreviewChange ];
};

export default usePreviewState;
