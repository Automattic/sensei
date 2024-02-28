/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import EditorWizardModal from './editor-wizard-modal';

// Hide welcome tour from WPCOM.
addFilter(
	'a8c.WpcomBlockEditorWelcomeTour.show',
	'sensei-lms/editor-wizard',
	() => false
);

registerPlugin( 'sensei-editor-wizard-plugin', {
	render: EditorWizardModal,
	icon: null,
} );
