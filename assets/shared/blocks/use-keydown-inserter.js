/**
 * WordPress dependencies
 */
import { createBlock } from '@wordpress/blocks';
import { select, useDispatch } from '@wordpress/data';
import { ENTER, BACKSPACE } from '@wordpress/keycodes';

/**
 * Insert or navigate to next sibling block on Enter. Remove current block on Backspace if empty.
 *
 * @param {Object}   props
 * @param {Function} props.insertBlocksAfter Callback to insert sibling block.
 * @param {string}   props.name              Current block name
 * @param {string}   props.clientId          Current block ID
 * @param {Object}   props.attributes        Current block attributes
 * @param {string}   props.attributes.title  Current block title
 * @return {{onKeyDown}} Keydown event handler
 */
export const useKeydownInserter = ( {
	insertBlocksAfter,
	name,
	clientId,
	attributes: { title },
} ) => {
	const { selectNextBlock, removeBlock } = useDispatch( 'core/block-editor' );

	/**
	 * Insert a new block on enter, unless there is already an empty new block after this one.
	 */
	const onEnter = () => {
		const editor = select( 'core/block-editor' );
		const nextBlock = editor.getBlock( editor.getNextBlockClientId() );

		if ( ! nextBlock || nextBlock.attributes.title ) {
			insertBlocksAfter( [ createBlock( name ) ] );
		} else {
			selectNextBlock( clientId );
		}
	};

	/**
	 * Remove block on backspace if it's empty.
	 *
	 * @param {Object}   e                Event object.
	 * @param {Function} e.preventDefault Prevent default function.
	 */
	const onBackspace = ( e ) => {
		if ( 0 === title.length ) {
			e.preventDefault();
			removeBlock( clientId );
		}
	};

	/**
	 * Handle key down.
	 *
	 * @param {Object} e         Event object.
	 * @param {number} e.keyCode Pressed key code.
	 */
	const onKeyDown = ( e ) => {
		switch ( e.keyCode ) {
			case ENTER:
				onEnter();
				break;
			case BACKSPACE:
				onBackspace( e );
				break;
		}
	};
	return { onKeyDown };
};
