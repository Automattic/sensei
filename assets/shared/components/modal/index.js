/**
 * WordPress dependencies
 */
import { Icon, close as closeIcon } from '@wordpress/icons';
import {
	useFocusOnMount,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalUseFocusOutside as useFocusOutside,
} from '@wordpress/compose';
import { ESCAPE } from '@wordpress/keycodes';
import { createPortal } from '@wordpress/element';

/**
 * Modal component.
 *
 * @param {Object}   props
 * @param {Function} props.onClose  Callback to run when trying to close the modal.
 * @param {string}   props.title    The title for the modal. Empty by default.
 * @param {Object}   props.children The content of the modal.
 * @return {JSX.Element} The modal component or null (if not open).
 */
const Modal = ( { onClose, title = '', children } ) => {
	const focusOnMountRef = useFocusOnMount();
	const focusOutsideProps = useFocusOutside( onClose );

	const handleEsc = ( event ) => {
		if ( event.keyCode === ESCAPE && ! event.defaultPrevented ) {
			onClose( event );
		}
	};

	return createPortal(
		<div className="sensei-modal">
			<div className="sensei-modal__overlay" />
			{ /* eslint-disable-next-line jsx-a11y/no-static-element-interactions */ }
			<div
				className="sensei-modal__wrapper"
				onKeyDown={ handleEsc }
				tabIndex="-1"
				ref={ focusOnMountRef }
				{ ...focusOutsideProps }
			>
				<div className="sensei-modal__header">
					<div className="sensei-modal__title">{ title }</div>
					<button
						className="sensei-modal sensei-modal__close-button"
						onClick={ onClose }
					>
						<Icon icon={ closeIcon } />
					</button>
				</div>
				<div className="sensei-modal__content">{ children }</div>
			</div>
		</div>,
		document.body
	);
};

export default Modal;
