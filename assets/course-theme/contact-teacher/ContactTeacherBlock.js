/**
 * External dependencies
 */
import { useState, useEffect, useCallback } from 'react';
import ReactModal from 'react-modal';

/**
 * WordPress dependencies
 */
import { Icon, close } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { FORM_STATUS } from './Constants';
import { modalParentSelector } from './modalParentSelector';
import { MessaageForm } from './MessageForm';
import { SubmitSuccess } from './SubmitSuccess';

/**
 * ContactTeacherBlock
 *
 * @param {Object}      props
 * @param {string}      props.nonceName  The name of the nonce field.
 * @param {string}      props.nonceValue The value of the nonce field.
 * @param {number}      props.postId     The id of the current post.
 * @param {HTMLElement} props.button     The DOM button element.
 */
export const ContactTeacherBlock = ( {
	nonceName,
	nonceValue,
	postId,
	button,
} ) => {
	const [ isOpen, setIsOpen ] = useState( false );
	const [ status, setStatus ] = useState( FORM_STATUS.SUCCESS );

	const handleOpen = useCallback( () => {
		setIsOpen( true );
	}, [] );

	const handleClose = useCallback( () => {
		setIsOpen( false );
	}, [] );

	useEffect( () => {
		button.addEventListener( 'click', handleOpen );
		return () => {
			button.removeEventListener( 'click', handleOpen );
		};
	}, [ button, handleOpen ] );

	return (
		<ReactModal
			overlayClassName="ReactModal__Overlay"
			className="ReactModal__Content"
			isOpen={ isOpen }
			onRequestClose={ handleClose }
			parentSelector={ modalParentSelector }
		>
			<div className="sensei-course-theme-contact-teacher__modal__container">
				<button
					className="sensei-course-theme-contact-teacher__modal__close"
					aria-label={ __( 'Close', 'sensei-lms' ) }
					onClick={ handleClose }
				>
					<Icon icon={ close } />
				</button>
				{ [
					FORM_STATUS.IDLE,
					FORM_STATUS.FAIL,
					FORM_STATUS.IN_PROGRESS,
				].includes( status ) && (
					<MessaageForm
						nonceName={ nonceName }
						nonceValue={ nonceValue }
						onStatusChange={ setStatus }
						postId={ postId }
						status={ status }
					/>
				) }
				{ FORM_STATUS.SUCCESS === status && <SubmitSuccess /> }
			</div>
		</ReactModal>
	);
};
