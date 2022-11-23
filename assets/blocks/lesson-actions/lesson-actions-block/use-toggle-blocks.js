/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { ACTION_BLOCKS, BLOCKS_DEFAULT_ATTRIBUTES } from './constants';

/**
 * Toggle blocks hook.
 *
 * @param {Object}   options                Hook options.
 * @param {string}   options.parentClientId Parent client ID.
 * @param {Function} options.setAttributes  Set attributes function.
 * @param {Object}   options.toggledBlocks  Toggled blocks, where the key is the block name.
 * @param {Object[]} options.blocks         Blocks to prepare to toggle.
 *
 * @return {Object[]} Blocks prepared to toggle.
 */
const useToggleBlocks = ( {
	parentClientId,
	setAttributes,
	toggledBlocks,
	blocks,
} ) => {
	const parentBlock = useSelect(
		( select ) => select( 'core/block-editor' ).getBlock( parentClientId ),
		[]
	);
	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );
	const [ blocksAttributes, setBlocksAttributes ] = useState( {} );

	/**
	 * Toggle block.
	 *
	 * @param {string} blockName Block name.
	 *
	 * @return {Function} Function to toggle the block.
	 */
	const toggleBlock = ( blockName ) => ( on ) => {
		const toggledBlock = parentBlock.innerBlocks.find(
			( i ) => i.name === blockName
		);
		let newBlocks = null;

		if ( on && ! toggledBlock ) {
			// Add block using the default attributes, and the previous attributes if it exists.
			newBlocks = [
				...parentBlock.innerBlocks,
				createBlock( blockName, {
					...BLOCKS_DEFAULT_ATTRIBUTES[ blockName ],
					...blocksAttributes[ blockName ],
				} ),
			].sort(
				( a, b ) =>
					ACTION_BLOCKS.indexOf( a.name ) -
					ACTION_BLOCKS.indexOf( b.name )
			);
		} else if ( ! on && toggledBlock ) {
			// Remove block.
			newBlocks = parentBlock.innerBlocks.filter(
				( i ) => i.name !== blockName
			);

			// Save block attributes to restore, if needed.
			setBlocksAttributes( ( attrs ) => ( {
				...attrs,
				[ blockName ]: toggledBlock.attributes,
			} ) );
		}

		if ( newBlocks ) {
			replaceInnerBlocks( parentClientId, newBlocks, false );
		}

		setAttributes( {
			toggledBlocks: { ...toggledBlocks, [ blockName ]: on },
		} );
	};

	return blocks.map( ( block ) => ( {
		active: false !== toggledBlocks[ block.blockName ],
		onToggle: toggleBlock( block.blockName ),
		label: block.label,
	} ) );
};

export default useToggleBlocks;
