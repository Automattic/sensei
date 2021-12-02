/**
 * External dependencies
 */
import { useState, useCallback } from 'react';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { FORM_STATUS } from './Constants';

/**
 * MessageForm
 *
 * @param {Object}   props
 * @param {string}   props.nonceName      The name of the nonce field.
 * @param {string}   props.nonceValue     The value of the nonce field.
 * @param {number}   props.postId         The id of the current post.
 * @param {string}   props.status         The status of the form.
 * @param {Function} props.onStatusChange The function that updates the status.
 */
export const MessaageForm = ( {
	nonceName,
	nonceValue,
	postId,
	status,
	onStatusChange,
} ) => {
	const [ message, setMessage ] = useState( '' );

	const handleMessageChange = useCallback( ( ev ) => {
		setMessage( ev.target.value || '' );
	}, [] );
	const handleSubmit = useCallback(
		async ( ev ) => {
			ev.preventDefault();
			if ( FORM_STATUS.IN_PROGRESS === status ) {
				return;
			}
			onStatusChange( FORM_STATUS.IN_PROGRESS );

			try {
				const result = await apiFetch( {
					path: '/sensei-internal/v1/messages',
					method: 'POST',
					data: {
						[ nonceName ]: nonceValue,
						post_id: postId,
						contact_message: message,
					},
				} );
				if ( result.success ) {
					onStatusChange( FORM_STATUS.SUCCESS );
				} else {
					onStatusChange( FORM_STATUS.FAIL );
				}
			} catch ( err ) {
				onStatusChange( FORM_STATUS.FAIL );
			}
		},
		[ status, onStatusChange, postId, nonceName, nonceValue, message ]
	);

	const inProgress = FORM_STATUS.IN_PROGRESS === status;

	return (
		<>
			<h1 className="sensei-course-theme-contact-teacher__form__title">
				{ __( 'Contact your teacher', 'sensei-lms' ) }
			</h1>
			<form method="POST" onSubmit={ handleSubmit }>
				<textarea
					required
					name="contact_message"
					rows="5"
					value={ message }
					onChange={ handleMessageChange }
					className="sensei-course-theme-contact-teacher__form__message"
					placeholder={ __( 'Enter your message', 'sensei-lms' ) }
					disabled={ inProgress }
				/>
				<input type="hidden" name={ nonceName } value={ nonceValue } />
				<input type="hidden" name="post_id" value={ postId } />
				<button
					type="submit"
					className={ `sensei-course-theme__button is-primary components_button ${
						inProgress ? 'is-busy' : ''
					}` }
					disabled={ inProgress }
				>
					{ __( 'Send message', 'sensei-lms' ) }
				</button>
			</form>
		</>
	);
};
