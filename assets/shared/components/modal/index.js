/**
 * WordPress dependencies
 */
import { Icon, close as closeIcon } from '@wordpress/icons';

/**
 * External dependencies
 */
import classNames from 'classnames';

const Modal = ( { isOpen, setIsOpen, title, children } ) => {
	const close = ( event ) => {
		setIsOpen( false );
		event.preventDefault();
	};

	return (
		<div
			className={ classNames( 'sensei-modal', {
				'sensei-modal--open': isOpen,
			} ) }
		>
			<button
				className="sensei-modal__overlay"
				aria-label="Close"
				onClick={ close }
			/>
			<div className="sensei-modal__wrapper">
				<div className="sensei-modal__header">
					<div className="sensei-modal__title">{ title }</div>
					<button
						className="sensei-modal__close-button"
						onClick={ close }
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
