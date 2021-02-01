/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useEffect, useState } from '@wordpress/element';

/**
 * Insert an empty lesson block to the end of the module when it's selected.
 *
 * @param {Object} opts
 * @param {string} opts.name       Block to be inserted.
 * @param {Object} opts.attributes Attributes of a new block.
 * @param {Object} parentProps     Block properties.
 */
export const useAutoInserter = ( { name, attributes = {} }, parentProps ) => {
	const [ autoBlockClientId, setAutoBlockClientId ] = useState( null );
	const { clientId, isSelected } = parentProps;
	const { insertBlock, removeBlock } = useDispatch( 'core/block-editor' );

	const createAndInsertBlock = useCallback( () => {
		const block = createBlock( name, attributes );
		insertBlock( block, undefined, clientId, false );
		setAutoBlockClientId( block.clientId );
	}, [ insertBlock, clientId, name, attributes ] );

	const blocks = useSelect( ( select ) =>
		select( 'core/block-editor' ).getBlocks( clientId )
	);

	const hasSelected =
		useSelect( ( select ) =>
			select( 'core/block-editor' ).hasSelectedInnerBlock( clientId )
		) || isSelected;

	useEffect( () => {
		const lastBlock = blocks.length && blocks[ blocks.length - 1 ];
		const hasEmptyLastBlock = lastBlock && ! lastBlock.attributes.title;

		if ( hasSelected && ! hasEmptyLastBlock && ! autoBlockClientId ) {
			createAndInsertBlock();
		}
		if ( ! hasSelected ) {
			if (
				hasEmptyLastBlock &&
				lastBlock.clientId === autoBlockClientId &&
				1 !== blocks.length
			) {
				removeBlock( lastBlock.clientId, false );
			}
			setAutoBlockClientId( null );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ hasSelected ] );
};
