/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * External dependencies
 */
import { has } from 'lodash';

export default function handleEmailBlocksEditor() {
	addFilter(
		'blocks.registerBlockType',
		'sensei-lms/email-blocks',
		removeIrrelevantSettings,
		10
	);

	/**
	 * Update the blocks to remove extra settings when used in email editor.
	 *
	 * @param {Object} settings Block settings.
	 */
	function removeIrrelevantSettings( settings ) {
		const supports = { ...( settings.supports ? settings.supports : {} ) };

		// Remove font family setting.
		if (
			has( settings, 'supports.typography.fontFamily' ) ||
			has( settings, 'supports.typography.__experimentalFontFamily' )
		) {
			supports.typography = {
				...supports.typography,
				__experimentalFontFamily: false,
				fontFamily: false,
			};
		}

		// Remove alignWide setting.
		if ( has( settings, 'supports.alignWide' ) ) {
			supports.alignWide = false;
		}

		// Remove wide from align options.
		if ( has( settings, 'supports.align.length' ) ) {
			supports.align = supports.align.filter( ( item ) => {
				return item !== 'wide';
			} );
		}

		return {
			...settings,
			supports,
		};
	}
}

handleEmailBlocksEditor();
