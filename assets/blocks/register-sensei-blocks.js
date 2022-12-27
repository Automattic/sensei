/**
 * External dependencies
 */
import { omit } from 'lodash';

/**
 * WordPress dependencies
 */
import { registerBlockType, updateCategory } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import LogoTreeIcon from '../icons/logo-tree.svg';

/**
 * Register Sensei blocks.
 *
 * @todo  Refactor how the metadata and settings are passed to `registerBlockType`.
 * @param {Array} blocks Blocks to be registered.
 */
const registerSenseiBlocks = ( blocks ) => {
	updateCategory( 'sensei-lms', {
		icon: <LogoTreeIcon width="20" height="20" />,
	} );

	blocks.forEach( ( block ) => {
		let { metadata, name, ...settings } = block;

		if ( metadata ) {
			// Remove the overlapping metadata keys from the settings object to make localization work.
			// This is needed because only the metadata object is localized, but the overlapping keys will be overwritten by the settings object and the localization is lost.
			settings = omit( settings, Object.keys( metadata ) );
		}

		// The metadata object should be used for the `block.json` strings to be localized.
		// See https://github.com/Automattic/sensei/pull/5782 for more details.
		registerBlockType( metadata || name, settings );
	} );
};

export default registerSenseiBlocks;
