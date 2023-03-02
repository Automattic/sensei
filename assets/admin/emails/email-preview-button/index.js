/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import EmailPreviewButton from './email-preview-button';

registerPlugin( 'sensei-email-preview-plugin', {
	render: EmailPreviewButton,
} );
