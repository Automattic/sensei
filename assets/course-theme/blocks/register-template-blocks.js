/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Makes sure the template blocks are only registered when in the site or widget editor, or editing the template from
 * the lesson page.
 *
 * @param {Array} blocks
 */
export function registerTemplateBlocks( blocks ) {
	blocks.forEach( ( block ) => {
		const { name, ...settings } = block;
		registerBlockType( name, settings );
	} );
}
