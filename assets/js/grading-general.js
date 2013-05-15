jQuery(document).ready( function($) {

	/***************************************************************************************************
	 * 	1 - Helper Functions.
	 ***************************************************************************************************/

	 /**
	 * exists checks if selector exists
	 * @since  1.2.0
	 * @return boolean
	 */
	jQuery.fn.exists = function() {
		return this.length>0;
	}

	/***************************************************************************************************
	 * 	2 - Grading Overview Functions.
	 ***************************************************************************************************/

	 /**
	 * Course Change Event.
	 *
	 * @since 1.3.0
	 * @access public
	 */
	jQuery( '#grading-course-options' ).on( 'change', '', function() {
		// Populate the Lessons select box
		var courseId = jQuery(this).val();
		var dataToPost = '';
	 	dataToPost += 'course_id' + '=' + courseId;
		// Perform the AJAX call to get the select box.
		jQuery.post(
			ajaxurl,
			{
				action : 'get_lessons_dropdown',
				get_lessons_dropdown_nonce : woo_localized_data.get_lessons_dropdown_nonce,
				data : dataToPost
			},
			function( response ) {
				// Check for a response
				if ( '' != response ) {
					// Empty the results div's
					jQuery( '#learners-to-grade' ).empty();
					jQuery( '#learners-graded' ).empty();
					// Populate the Lessons drop down
					jQuery( '#grading-lesson-options' ).empty().append( response );
					// Add Chosen to the drop down
					if ( jQuery( '#grading-lesson-options' ).exists() ) {
						// Show the Lessons label
						jQuery( '#grading-lesson-options-label' ).show();
						if ( jQuery( '#grading-lesson-options' ).hasClass( 'chzn-done' ) ) {
							jQuery( '#grading-lesson-options' ).trigger("liszt:updated");
						} else {
							jQuery( '#grading-lesson-options' ).chosen();
						} // End If Statement
					} // End If Statement
				} else {
					// Failed
				}
			}
		);
		return false;
	});

	/**
	 * Lesson Change Event.
	 *
	 * @since 1.3.0
	 * @access public
	 */
	jQuery( '#grading-lesson-options' ).on( 'change', '', function() {
		// Populate the Lessons select box
		var lessonId = jQuery(this).val();
		var dataToPost = '';
	 	dataToPost += 'lesson_id' + '=' + lessonId;
		// Perform the AJAX call to get the select box.
		jQuery.post(
			ajaxurl,
			{
				action : 'get_lessons_html',
				get_lessons_html_nonce : woo_localized_data.get_lessons_html_nonce,
				data : dataToPost
			},
			function( response ) {
				// Check for a response
				if ( '' != response ) {
					console.log(response);
					jQuery( '#learners-to-grade' ).append( 'A learner to grade' );
					jQuery( '#learners-graded' ).append( 'A learner already graded' );
				} else {
					// Failed
				}
			}
		);
		return false;
	});

	/***************************************************************************************************
	 * 	3 - Grading User Profile Functions.
	 ***************************************************************************************************/



	/***************************************************************************************************
	 * 	4 - Load Chosen Dropdowns.
	 ***************************************************************************************************/

	// Grading Overview Drop Downs
	if ( jQuery( '#grading-course-options' ).exists() ) { jQuery( '#grading-course-options' ).chosen(); }


});