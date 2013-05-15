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
	 * 	3 - Grading User Quiz Functions.
	 ***************************************************************************************************/

	 jQuery( '.grading-mark' ).on( 'click', 'input', function() {
	 	var input_name = this.name;
	 	var input_value = this.value;

	 	var changed = true;
	 	var existing_choice = jQuery( '#' + input_name + '_grade_choice' ).val();
	 	if( existing_choice == input_value ) { changed = false; }
	 	jQuery( '#' + input_name + '_grade_choice' ).val( input_value );

	 	var bgcolor;
	 	if( input_value == 'right' ) { bgcolor = '#AEE7AE'; } else { bgcolor = '#FFC0C0'; }
	 	jQuery( '#' + this.name + '_box .user-answer' ).css( 'background', bgcolor );

	 	if( changed ) {
	 		var total_grade = parseInt( jQuery( '#total_grade' ).val() );
	 		var question_grade = parseInt( jQuery( '#' + this.name + '_grade' ).val() );
	 		var new_grade = total_grade;
	 		if( input_value == 'right' ) {
	 			new_grade = ( total_grade + question_grade );
	 		} else if( input_value == 'wrong' && existing_choice != '' ) {
	 			new_grade = ( total_grade - question_grade );
	 		}
	 		jQuery( '#total_grade' ).val( new_grade );
	 	}
	 });

	 jQuery( '.sensei-grading-main .buttons' ).on( 'click', 'input.reset-button', function() {
	 	jQuery( '.question_box .user-answer' ).css( 'background', '#F5F5F5' );
	 });


	/***************************************************************************************************
	 * 	4 - Load Chosen Dropdowns.
	 ***************************************************************************************************/

	// Grading Overview Drop Downs
	if ( jQuery( '#grading-course-options' ).exists() ) { jQuery( '#grading-course-options' ).chosen(); }


});