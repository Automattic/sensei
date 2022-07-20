/**
 * WordPress dependencies
 */
import { Modal, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { ENTER } from '@wordpress/keycodes';

/**
 * Controlled Component that shows a modal containing a confirm dialog. Inspired by Gutenberg's experimental
 * Confirm Dialog.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/components/confirm-dialog/
 * @param {Object}   props                   Component props.
 * @param {boolean}  props.isOpen            Determines if the confirm dialog is open or not.
 * @param {string}   props.title             Title for the confirm dialog.
 * @param {string}   props.children          Content for the confirm dialog, can be any React component.
 * @param {Function} props.onConfirm         Callback called when the user click on "OK" or press Enter with the modal open.
 * @param {Function} props.onCancel          Callback called when the user click on "Cancel" or press ESC with the modal open.
 * @param {string}   props.confirmButtonText Optional custom text to display as the confirmation button's label.
 * @param {string}   props.cancelButtonText  Optional custom text to display as the cancellation button's label.
 */
const ConfirmDialog = ( {
	isOpen = false,
	title,
	children,
	onConfirm,
	onCancel,
	cancelButtonText = __( 'Cancel', 'sensei-lms' ),
	confirmButtonText = __( 'OK', 'sensei-lms' ),
} ) => {
	useConfirmOnEnter( isOpen, onConfirm );
	if ( ! isOpen ) {
		return null;
	}
	return (
		<Modal
			title={ title }
			onRequestClose={ onCancel }
			shouldCloseOnClickOutside={ false }
			className="sensei-confirm-dialog"
		>
			<div className="sensei-confirm-dialog__message">{ children }</div>
			<div className="sensei-confirm-dialog__button-container">
				<Button
					variant="tertiary"
					onClick={ onCancel }
					className="sensei-confirm-dialog__button"
				>
					{ cancelButtonText }
				</Button>
				<Button
					variant="primary"
					onClick={ onConfirm }
					className="sensei-confirm-dialog__button"
				>
					{ confirmButtonText }
				</Button>
			</div>
		</Modal>
	);
};

/**
 * Calls onConfirm when registerListener is true and the user press ENTER.
 *
 * @param {boolean}  shouldRegisterListener If the listener should be set up or not.
 * @param {Function} fn                     The callback to call when the user press ENTER, if registerListener is true.
 */
const useConfirmOnEnter = ( shouldRegisterListener, fn ) => {
	useEffect( () => {
		if ( ! shouldRegisterListener ) {
			return;
		}
		const callback = ( event ) => {
			if ( event.keyCode === ENTER && ! event.defaultPrevented ) {
				event.preventDefault();
				fn();
			}
		};
		document.body.addEventListener( 'keydown', callback, false );
		return () =>
			document.body.removeEventListener( 'keydown', callback, false );
	}, [ shouldRegisterListener, fn ] );
};

export default ConfirmDialog;
