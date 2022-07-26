/**
 * WordPress dependencies
 */
import { Icon, close as closeIcon } from '@wordpress/icons';

/**
 * Modal component.
 *
 * @param {Object}   props
 * @param {boolean}  props.isOpen      Whether the modal should be open or not.
 * @param {Function} props.handleClose Callback to run when trying to close the modal.
 * @param {string}   props.title       The title for the modal. Empty by default.
 * @param {Object}   props.children    The content of the modal.
 * @return {JSX.Element} The modal component or null (if not open).
 */
const Modal = ( { isOpen, handleClose, title = '', children } ) => {
	// Return null if the modal is not open.
	if ( ! isOpen ) {
		return null;
	}

	return (
		<div className={ 'sensei-modal' }>
			<button
				className="sensei-modal sensei-modal__overlay"
				aria-label="Close"
				onClick={ handleClose }
			/>
			<div className="sensei-modal__wrapper">
				<div className="sensei-modal__header">
					<div className="sensei-modal__title">{ title }</div>
					<button
						className="sensei-modal sensei-modal__close-button"
						onClick={ handleClose }
					>
						<Icon icon={ closeIcon } />
					</button>
				</div>
				<div className="sensei-modal__content">{ children }</div>
			</div>
		</div>
	);
};

export default Modal;
