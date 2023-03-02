/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { registerPlugin } from '@wordpress/plugins';
import { useState, createPortal } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';

/**
 * The email preview link.
 *
 * @return {Object|null} A portal element or null if the container is not available.
 */
const EmailPreviewButton = () => {
	const [ isSaving, setIsSaving ] = useState( false );
	const { autosave, savePost } = useDispatch( editorStore );
	const { postId, isSaveable, isAutosaveable, isLocked, isDraft } = useSelect(
		( select ) => ( {
			postId: select( editorStore ).getCurrentPostId(),
			isSaveable: select( editorStore ).isEditedPostSaveable(),
			isAutosaveable: select( editorStore ).isEditedPostAutosaveable(),
			isLocked: select( editorStore ).isPostLocked(),
			isDraft:
				[ 'draft', 'auto-draft' ].indexOf(
					select( editorStore ).getEditedPostAttribute( 'status' )
				) !== -1,
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

		if ( isAutosaveable && ! isLocked ) {
			setIsSaving( true );

			if ( isDraft ) {
				await savePost( { isPreview: true } );
			} else {
				await autosave( { isPreview: true } );
			}

			setIsSaving( false );
		}

		window.open( event.target.href, 'sensei-email-preview-' + postId );
	};

	return createPortal(
		<Button
			href={ window.sensei_email_preview.link }
			className="sensei-email-preview-button"
			variant="tertiary"
			onClick={ openPreviewWindow }
			isBusy={ isSaving }
			disabled={ ! isSaveable || isSaving }
		>
			{ __( 'Preview', 'sensei-lms' ) }
		</Button>,
		container
	);
};

registerPlugin( 'sensei-email-preview-plugin', {
	render: EmailPreviewButton,
} );
