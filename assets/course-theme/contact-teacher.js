/**
 * @module ContactTeacher
 * @description Responsible for making a seamless ajax post of the
 * contact teacher form, without refreshing the whole page.
 */

/**
 * Handles the contact teacher submit event.
 *
 * @param {Object} ev The contact teacher form submit event.
 */
export function submitContactTeacher( ev ) {
	// If the fetch api is not available then bail.
	if ( ! window.fetch ) {
		return;
	}

	const apiBaseUrl = document.querySelector(
		'link[rel="https://api.w.org/"]'
	)?.href;
	// If the rest api is not available then bail.
	if ( ! apiBaseUrl ) {
		return;
	}

	// Prevent browser from refreshing.
	ev.preventDefault();

	const form = ev.target;
	const submitButton = form.querySelector(
		'button.sensei-contact-teacher-form__submit'
	);
	const closeButton = form.parentElement.querySelector(
		'.sensei-contact-teacher-close'
	);
	submitButton.classList.add( 'sensei-course-theme__button', 'is-busy' );
	submitButton.disabled = true;

	const fieldNames = [
		'sensei_message_teacher_nonce',
		'_wpnonce',
		'post_id',
		'contact_message',
	];
	const values = fieldNames.reduce(
		( acc, name ) => ( {
			...acc,
			[ name ]: form.elements[ name ].value,
		} ),
		{}
	);

	window
		.fetch( `${ apiBaseUrl }sensei-internal/v1/messages?_locale=user`, {
			method: 'POST',
			body: JSON.stringify( values ),
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': values._wpnonce,
			},
		} )
		.then( () => {
			form.classList.add( 'is-success' );
			closeButton.focus();
		} )
		.catch( () => {
			// TODO: Show submit failed message.
		} );
}
