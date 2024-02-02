import { __ } from '@wordpress/i18n';

/**
 * Add placeholder to tinymce editor
 *
 * @param  editor tinymce editor.
 */
window.addPlaceholderInTinymceEditor = ( editor ) => {
	// Remove placeholder on submit.
	jQuery( '#sensei-quiz-form' ).submit( function () {
		editor.dom.remove( 'multi-line-placeholder' );
		return true;
	} );

	// Add placeholder on init and blur.
	editor.on( 'blur init', function () {
		if ( editor.getContent() == '' ) {
			editor.setContent(
				"<p id='multi-line-placeholder'>" +
					__( 'Your answer', 'sensei-lms' ) +
					'</p>'
			);
		}
	} );
	// Remove placeholder on focus.
	editor.on( 'focus', function () {
		editor.dom.remove( 'multi-line-placeholder' );
	} );
};
