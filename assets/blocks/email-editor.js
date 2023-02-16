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
		if (
			has( settings, 'supports.typography.fontFamily' ) ||
			has( settings, 'supports.typography.__experimentalFontFamily' )
		) {
			settings = {
				...settings,
				supports: {
					...settings.supports,
					typography: {
						...settings.supports.typography,
						__experimentalFontFamily: false,
						fontFamily: false,
					},
				},
			};
		}

		return settings;
	}
}

handleEmailBlocksEditor();
