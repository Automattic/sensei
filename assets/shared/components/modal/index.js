/**
 * External dependencies
 */
import classnames from 'classnames';

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
import { __ } from '@wordpress/i18n';

/**
 * Modal component.
 *
 * @param {Object}   props
 * @param {string}   props.className    A class name for the modal.
 * @param {Function} props.onClose      Callback to run when trying to close the modal.
 * @param {string}   props.title        The title for the modal. Empty by default.
 * @param {Function} props.renderFooter Render Prop to render the footer. Will be passed the "onClose" as a parameter.
 * @param {Object}   props.children     The content of the modal.
 */
const Modal = ( {
	className,
	onClose,
	title = '',
	renderFooter,
	children,
} ) => {
	const focusOnMountRef = useFocusOnMount();
	const focusOutsideProps = useFocusOutside( onClose );

	const handleEsc = ( event ) => {
		if ( event.keyCode === ESCAPE && ! event.defaultPrevented ) {
			onClose( event );
		}
	};

	return createPortal(
		<div className={ classnames( 'sensei-modal', className ) }>
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
					{ title && (
						<div className="sensei-modal__title">{ title }</div>
					) }
					<button
						className="sensei-modal sensei-modal__close-button"
						onClick={ onClose }
						aria-label={ __( 'Close', 'sensei-lms' ) }
					>
						<Icon icon={ closeIcon } />
					</button>
				</div>
				<div className="sensei-modal__content">{ children }</div>
				{ renderFooter && (
					<div className="sensei-modal__footer">
						{ renderFooter( onClose ) }
					</div>
				) }
			</div>
		</div>,
		document.body
	);
};

export default Modal;
