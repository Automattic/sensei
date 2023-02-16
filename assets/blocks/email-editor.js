/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';

addFilter(
	'blocks.registerBlockType',
	'sensei-lms/email-blocks',
	removeIrrelevantSettings
);

/**
 * Update the blocks to remove extra settings for emails.
 *
 * @param {Object} settings Block settings.
 */
function removeIrrelevantSettings( settings ) {
	if (
		settings &&
		settings.supports &&
		settings.supports.typography &&
		settings.supports.typography.__experimentalFontFamily
	) {
		settings = {
			...settings,
			supports: {
				...settings.supports,
				typography: {
					...settings.supports.typography,
					__experimentalFontFamily: false,
				},
			},
		};
	}

	return settings;
}
