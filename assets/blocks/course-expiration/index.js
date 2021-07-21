/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import SettingsPanel from './settings-panel';

if ( window.sensei_single_course_blocks.feature_flag_course_expiration ) {
	registerPlugin( 'plugin-document-setting-panel-demo', {
		render: SettingsPanel,
		icon: 'clock',
	} );
}
