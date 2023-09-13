/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

/**
 * Handles uploading a file for a file upload question.
 *
 */
domReady( () => {
	document
		.querySelectorAll( '.sensei-lms-question-block__file-input' )
		.forEach( ( fileInput ) => {
			fileInput.addEventListener( 'change', ( event ) => {
				const input = event.target;
				const file = input.files?.[ 0 ];
				const fileUploadName = input.parentElement.parentElement.querySelector(
					'.sensei-lms-question-block__file-upload-name'
				);

				if ( fileUploadName ) {
					fileUploadName.innerText = file && file.name;
				}
			} );
		} );
} );
