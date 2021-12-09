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
function handleSubmit( ev ) {
	// If the fetch api is not available then bail.
	if ( ! window.fetch ) {
		return;
	}

	ev.preventDefault();
	const form = ev.target;
	const submitButton = ev.target.querySelector(
		'button.sensei-contact-teacher-form__submit'
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
			[ name ]: form.querySelector( `[name="${ name }"]` ).value,
		} ),
		{}
	);

	window
		.fetch( '/wp-json/sensei-internal/v1/messages?_locale=user', {
			method: 'POST',
			body: JSON.stringify( values ),
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': values._wpnonce,
			},
		} )
		.then( () => {
			form.parentElement
				.querySelector( '.sensei-contact-teacher-success' )
				.classList.add( 'show' );
			submitButton.classList.remove( 'is-busy' );
			submitButton.disabled = false;
		} )
		.catch( () => {
			// TODO: Show submit failed message.
			submitButton.classList.remove( 'is-busy' );
			submitButton.disabled = false;
		} );
}

// eslint-disable-next-line @wordpress/no-global-event-listener
window.addEventListener( 'load', function () {
	document
		.querySelectorAll(
			'.sensei-course-theme__frame .sensei-contact-teacher-form'
		)
		.forEach( ( form ) => {
			form.addEventListener( 'submit', handleSubmit );
		} );

	document
		.querySelectorAll( '.sensei-contact-teacher-open' )
		.forEach( ( openButton ) => {
			openButton.addEventListener( 'click', () => {
				document
					.querySelectorAll( '.sensei-contact-teacher-success' )
					.forEach( ( successElement ) => {
						successElement.classList.remove( 'show' );
					} );
			} );
		} );
} );
