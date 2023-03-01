/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { registerPlugin } from '@wordpress/plugins';
import { createPortal } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';

/**
 * Email preview link.
 */
const EmailPreviewLink = () => {
	const { autosave } = useDispatch( editorStore );
	const { isSaveable, isAutosaveable, isLocked } = useSelect(
		( select ) => ( {
			isSaveable: select( editorStore ).isEditedPostSaveable(),
			isAutosaveable: select( editorStore ).isEditedPostAutosaveable(),
			isLocked: select( editorStore ).isPostLocked(),
		} )
	);

	const container = document.querySelector(
		'.block-editor-post-preview__dropdown'
	);
	if ( ! container ) {
		return null;
	}

	/**
	 * Open the preview in a new window. Triggers an autosave when needed.
	 *
	 * @param {MouseEvent} event
	 * @return {Promise<void>}
	 */
	const openPreviewWindow = async ( event ) => {
		event.preventDefault();

		const previewWindow = window.open();
		previewWindow.focus();

		if ( isAutosaveable && ! isLocked ) {
			await autosave();
		}

		previewWindow.location = event.target.href;
	};

	return createPortal(
		<Button
			href={ window.sensei_email_preview.link }
			className="components-button"
			variant="tertiary"
			target="_blank"
			onClick={ openPreviewWindow }
			disabled={ ! isSaveable }
		>
			{ __( 'Preview', 'sensei-lms' ) }
		</Button>,
		container
	);
};

registerPlugin( 'sensei-email-preview-plugin', {
	render: EmailPreviewLink,
} );
