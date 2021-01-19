import { useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';

import { PREVIEW_STATE } from './constants';

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

	/**
	 * Preview change handler.
	 *
	 * @param {string} newPreviewState New preview state.
	 */
	const onPreviewChange = ( newPreviewState ) => {
		const newBlocks = lessonActionsBlock.innerBlocks.map( ( block ) => ( {
			...block,
			attributes: {
				...block.attributes,
				disabled: ! PREVIEW_STATE[ newPreviewState ].includes(
					block.name
				),
			},
		} ) );

		replaceInnerBlocks( parentClientId, newBlocks, false );

		setPreviewState( newPreviewState );
	};

	return [ previewState, onPreviewChange ];
};

export default usePreviewState;
