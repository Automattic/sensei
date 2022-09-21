/**
 * WordPress dependencies
 */
import { registerBlockType, unregisterBlockType } from '@wordpress/blocks';
import { select, subscribe } from '@wordpress/data';

/**
 * Makes sure the template blocks are only registered when in the site or widget editor, or editing the template from
 * the lesson page.
 *
 * @param {Array} blocks
 */
export function registerTemplateBlocks( blocks ) {
	let themeBlocksEnabled = false;

	const toggleBlockRegistration = ( enable ) => {
		if ( enable === themeBlocksEnabled ) {
			return;
		}
		themeBlocksEnabled = enable;
		const method = enable ? registerBlockType : unregisterBlockType;
		blocks.forEach( ( block ) => {
			const { name, ...settings } = block;
			method( name, settings );
		} );
	};

	toggleBlockRegistration( true );

	// TODO Only subscribe when in the post editor.
	subscribe( () => {
		const postType = select( 'core/editor' )?.getCurrentPostType();
		const editPost = select( 'core/edit-post' );

		if ( ! postType || ! editPost ) {
			return;
		}

		const isTemplate =
			'lesson' === postType && editPost.isEditingTemplate();
		toggleBlockRegistration( isTemplate );
	} );
}
