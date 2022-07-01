/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import EditorWizardModal from './editor-wizard-modal';

registerPlugin( 'sensei-editor-wizard-plugin', {
	render: EditorWizardModal,
	icon: null,
} );
