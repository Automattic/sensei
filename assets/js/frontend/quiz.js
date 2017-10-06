/**
 * Functionality for quiz pages.
 */

jQuery( function() {

	// Keep track of which input was used to submit the form. Note that if the user
	// presses <Enter>, this click event should still be triggered.
	jQuery( 'form[data-action-on-empty-response=warn] input[type=submit]' ).on( 'click', function( event ) {
		jQuery( event.target ).attr( 'clicked', 'true' );
	} );

	// Warn on submitting empty responses. Note: using event delegation here to
	// ensure that this callback happens *after* the WP Editor callbacks, so we
	// have its value in the form data.
	jQuery( 'html' ).on( 'submit', 'form[data-action-on-empty-response=warn]', function( event ) {
		var form = jQuery( event.target );

		// Get, and reset, the button that was clicked
		var button = form.find( '[clicked=true]' );
		button.removeAttr( 'clicked' );

		// Only warn for the initial "Complete Quiz" button
		if ( button.attr( 'name' ) != 'quiz_complete' || button[0].hasAttribute( 'data-no-warn' ) ) {
			return;
		}

		const inputs = jQuery( event.target ).serializeArray();

		// Count how many questions are empty
		var emptyQuestions = countEmptyQuestions( inputs );

		if ( emptyQuestions > 0 ) {
			// Show the warning dialog
			event.preventDefault();
			showWarningDialog( true );
		}
	} );

	// Hide the warning dialog when user clicks cancel
	jQuery( '#sensei-empty-response-warning [data-cancel]' ).on( 'click', function (event) {
		event.preventDefault();
		showWarningDialog( false );
	} )


	// Helper functions

	// Count how many questions have an empty value
	function countEmptyQuestions( serializedInputs ) {
		var count = 0;

		for ( var i = 0; i < serializedInputs.length; i++ ) {
			var question = serializedInputs[i];

			if ( question[ 'name' ] == 'questions_asked[]' ) {
				count += 1;
			}

			if ( matches = question[ 'name' ].match( /^sensei_question\[([0-9]*)\]/ ) ) {
				var id = matches[1];

				if ( questionHasValue( id, question[ 'value' ] ) ) {
					count -= 1;
				}
			}
		}

		return count;
	}

	// Determine whether the question has a non-empty value, taking file uploads
	// into account
	function questionHasValue( questionID, questionValue ) {
		var val = questionValue;

		if ( val.length <= 0 ) {
			// Check if it's a file upload
			val = jQuery( 'input[type=file][name=file_upload_' + questionID + ']' ).val();
		}

		return val && val.length > 0;
	}

	// Show or hide the warning dialog
	function showWarningDialog( showDialog ) {
		if ( showDialog ) {
			jQuery( '#sensei-empty-response-warning' ).show();
			jQuery( '#sensei-quiz-submit-buttons' ).hide();
		} else {
			jQuery( '#sensei-empty-response-warning' ).hide();
			jQuery( '#sensei-quiz-submit-buttons' ).show();
		}
	}

} );
