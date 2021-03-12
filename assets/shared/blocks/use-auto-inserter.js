/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useEffect, useState } from '@wordpress/element';
/**
 * External dependencies
 */
import { noop } from 'lodash';

/**
 * Insert an empty inner block to the end of the block when it's selected.
 *
 * @param {Object}  opts
 * @param {string}  opts.name             Block to be inserted.
 * @param {boolean} opts.selectFirstBlock Select inserted block if it's the first one.
 * @param {Object}  opts.attributes       Attributes of a new block.
 * @param {Object}  parentProps           Block properties.
 */
export const useAutoInserter = (
	{ name, attributes = {}, selectFirstBlock = false },
	parentProps
) => {
	const [ autoBlockClientId, setAutoBlockClientId ] = useState( null );
	const { clientId, isSelected } = parentProps;
	const {
		__unstableMarkNextChangeAsNotPersistent: markNextChangeAsNotPersistent = noop,
		insertBlock,
		removeBlock,
	} = useDispatch( 'core/block-editor' );

	const blocks = useSelect( ( select ) =>
		select( 'core/block-editor' ).getBlocks( clientId )
	);

	const isFirstBlock = 0 === blocks.length;

	const createAndInsertBlock = useCallback( () => {
		const block = createBlock( name, attributes );
		const updateSelection = isFirstBlock && selectFirstBlock;
		markNextChangeAsNotPersistent();
		insertBlock( block, undefined, clientId, updateSelection );
		setAutoBlockClientId( block.clientId );
	}, [
		markNextChangeAsNotPersistent,
		insertBlock,
		clientId,
		name,
		attributes,
		isFirstBlock,
		selectFirstBlock,
	] );

	const hasSelected =
		useSelect( ( select ) =>
			select( 'core/block-editor' ).hasSelectedInnerBlock(
				clientId,
				true
			)
		) || isSelected;

	const lastBlock = blocks.length && blocks[ blocks.length - 1 ];
	const hasEmptyLastBlock = lastBlock && ! lastBlock.attributes.title;
	const hasAutoBlock =
		null !==
		useSelect(
			( select ) =>
				autoBlockClientId &&
				select( 'core/block-editor' ).getBlock( autoBlockClientId ),
			[ autoBlockClientId ]
		);

	useEffect( () => {
		if (
			hasSelected &&
			! hasEmptyLastBlock &&
			( ! hasAutoBlock || lastBlock.clientId === autoBlockClientId )
		) {
			createAndInsertBlock();
		}
		if ( ! hasSelected ) {
			if (
				hasEmptyLastBlock &&
				lastBlock.clientId === autoBlockClientId &&
				1 !== blocks.length
			) {
				markNextChangeAsNotPersistent();
				removeBlock( lastBlock.clientId, false );
			}
			setAutoBlockClientId( null );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ hasSelected, hasEmptyLastBlock, hasAutoBlock ] );

	const isAutoBlockSelected = useSelect(
		( select ) =>
			autoBlockClientId &&
			select( 'core/block-editor' ).isBlockSelected( autoBlockClientId ),
		[ autoBlockClientId ]
	);

	useEffect( () => {
		if ( isAutoBlockSelected ) {
			setAutoBlockClientId( null );
		}
	}, [ isAutoBlockSelected ] );
};
