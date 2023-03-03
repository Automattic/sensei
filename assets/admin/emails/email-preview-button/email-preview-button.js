/**
 * WordPress dependencies
 */
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import { useState, useEffect, useRef, createPortal } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';

/**
 * The email preview button component.
 *
 * @param {Object}  props
 * @param {string}  props.previewLink    The preview link.
 * @param {number}  props.postId         The post ID.
 * @param {boolean} props.isSaveable     If the post is savable.
 * @param {boolean} props.isAutosaveable If the post can be autosaved.
 * @param {boolean} props.isLocked       If the post is locked.
 * @param {boolean} props.isDraft        If the post is a draft.
 * @param {Object}  props.autosave       The autosave action.
 * @param {Object}  props.savePost       The save post action.
 *
 * @return {Object|null} A portal element or null if the container is not available.
 */
export const EmailPreviewButton = ( {
	previewLink,
	postId,
	isSaveable,
	isAutosaveable,
	isLocked,
	isDraft,
	autosave,
	savePost,
} ) => {
	const [ isSaving, setIsSaving ] = useState( false );
	const containerEl = useRef( null );

	useEffect( () => {
		containerEl.current = document.querySelector(
			'.block-editor-post-preview__dropdown'
		);
	} );

	if ( ! containerEl.current ) {
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
		containerEl.current
	);
};

export default compose( [
	withSelect( ( select ) => {
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
	} ),
	withDispatch( ( dispatch ) => ( {
		autosave: dispatch( editorStore ).autosave,
		savePost: dispatch( editorStore ).savePost,
	} ) ),
] )( EmailPreviewButton );
