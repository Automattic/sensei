/**
 * WordPress dependencies
 */
import { Icon, close as closeIcon } from '@wordpress/icons';
import { useEffect } from '@wordpress/element';

/**
 * Modal component.
 *
 * @param {Object}   props
 * @param {Function} props.handleClose Callback to run when trying to close the modal.
 * @param {string}   props.title       The title for the modal. Empty by default.
 * @param {Object}   props.children    The content of the modal.
 * @return {JSX.Element} The modal component or null (if not open).
 */
const Modal = ( { handleClose, title = '', children } ) => {
	useRunOnEscape( handleClose );

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

const useRunOnEscape = ( handleClose ) => {
	useEffect( () => {
		const handleEsc = ( event ) => {
			if ( event.keyCode === 27 ) {
				handleClose( event );
			}
		};
		// Attach close event on Escape key.
		// eslint-disable-next-line @wordpress/no-global-event-listener
		window.addEventListener( 'keydown', handleEsc );

		return () => {
			// Detach from keydown on component unmounting.
			// eslint-disable-next-line @wordpress/no-global-event-listener
			window.removeEventListener( 'keydown', handleEsc );
		};
	}, [ handleClose ] );
};
export default Modal;
