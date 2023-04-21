/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect, createPortal } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';

/**
 * The email preview button component.
 *
 * @return {Object|null} A portal element or null if the container is not available.
 */
export const EmailPreviewButton = () => {
	const {
		postId,
		isSaveable,
		isAutosaveable,
		isLocked,
		isDraft,
		previewLink,
	} = useSelect( ( select ) => {
		return {
			postId: select( editorStore ).getCurrentPostId(),
			isSaveable: select( editorStore ).isEditedPostSaveable(),
			isAutosaveable: select( editorStore ).isEditedPostAutosaveable(),
			isLocked: select( editorStore ).isPostLocked(),
			isDraft:
				[ 'draft', 'auto-draft' ].indexOf(
					select( editorStore ).getEditedPostAttribute( 'status' )
				) !== -1,
			previewLink: window.sensei_email_preview.link,
		};
	} );
	const { savePost, autosave } = useDispatch( editorStore );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ container, setContainer ] = useState( null );

	useEffect( () => {
		setContainer(
			document.querySelector( '.block-editor-post-preview__dropdown' )
		);
	}, [] );

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
			href={ previewLink }
			onClick={ openPreviewWindow }
			isBusy={ isSaving }
			disabled={ ! isSaveable || isSaving }
			className="sensei-email-preview-button"
			variant="tertiary"
			role="menuitem"
		>
			{ __( 'Preview', 'sensei-lms' ) }
		</Button>,
		container
	);
};

export default EmailPreviewButton;
