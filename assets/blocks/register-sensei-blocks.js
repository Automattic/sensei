/**
 * WordPress dependencies
 */
import { registerBlockType, updateCategory } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import SenseiIcon from '../icons/sensei.svg';

/**
 * Register Sensei blocks.
 *
 * @param {Array} blocks Blocks to be registered.
 */
const registerSenseiBlocks = ( blocks ) => {
	updateCategory( 'sensei-lms', {
		icon: <SenseiIcon width="20" height="20" />,
	} );

	blocks.forEach( ( block ) => {
		const { metadata, name, ...settings } = block;

		// For the block to be fully translatable, the `block.json` metadata object should be passed.
		// @see https://github.com/Automattic/sensei/pull/5782
		registerBlockType( metadata || name, settings );
	} );
};

export default registerSenseiBlocks;
