/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useEffect } from '@wordpress/element';
/**
 * External dependencies
 */
import { noop } from 'lodash';

/**
 * Insert an empty inner block to the end of the block when it's selected.
 *
 * @param {Object}   opts
 * @param {string}   opts.name             Block to be inserted.
 * @param {boolean}  opts.selectFirstBlock Select inserted block if it's the first one.
 * @param {Object}   opts.attributes       Attributes of a new block.
 * @param {Function} opts.isEmptyBlock     Callback to check if block is empty.
 * @param {Object}   parentProps           Block properties.
 */
export const useAutoInserter = (
	{ name, attributes = {}, selectFirstBlock = false, isEmptyBlock },
	parentProps
) => {
	const { clientId } = parentProps;
	const {
		__unstableMarkNextChangeAsNotPersistent: markNextChangeAsNotPersistent = noop,
		insertBlock,
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
	}, [
		markNextChangeAsNotPersistent,
		insertBlock,
		clientId,
		name,
		attributes,
		isFirstBlock,
		selectFirstBlock,
	] );

	const lastBlock = blocks.length && blocks[ blocks.length - 1 ];
	const hasEmptyLastBlock = lastBlock && isEmptyBlock( lastBlock.attributes );

	useEffect( () => {
		if ( ! hasEmptyLastBlock ) {
			createAndInsertBlock();
		}
	}, [ hasEmptyLastBlock, createAndInsertBlock ] );
};
