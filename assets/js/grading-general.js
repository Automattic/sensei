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

	/**
	 * Calculates the total grade based on the questions already graded
	 * @return void
	 */
	jQuery.fn.calculateTotalGrade = function() {
		var question_id;
	 	var question_grade;
	 	var total_grade = 0;
	 	var total_graded_questions = 0;
	 	jQuery( '.question_box.user_right' ).each( function() {
	 		question_id = jQuery( this ).find( '.question_id' ).val();
	 		question_grade = parseInt( jQuery( this ).find( '#question_' + question_id + '_grade' ).val() );
	 		total_grade += question_grade
	 		total_graded_questions++;
	 	});
	 	jQuery( '.question_box.user_wrong' ).each( function() {
	 		total_graded_questions++;
	 	});

	 	jQuery( '#total_graded_questions' ).val( total_graded_questions );

	 	var total_questions = parseInt( jQuery( '#total_questions' ).val() );
	 	var percent = parseFloat( total_grade * 100 / total_questions ).toFixed(2);
	 	percent = percent.replace( '.00', '' );

	 	jQuery( '#total_grade' ).val( total_grade );
	 	jQuery( '.total_grade_total' ).html( total_grade );
	 	jQuery( '.total_grade_percent' ).html( percent );
	 	jQuery( '.total_grade_count' ).html( total_questions );

	 	if( total_questions == total_graded_questions ) {
			jQuery( '#all_questions_graded' ).val( 'yes' );
			jQuery( '.grade-button' ).val( 'Grade' );
	 	} else {
	 		jQuery( '#all_questions_graded' ).val( 'no' );
	 		jQuery( '.grade-button' ).val( 'Save' );
	 	}
	}

	/**
	 * Automatically grades questions where possible
	 * @return void
	 */
	jQuery.fn.autoGrade = function() {
		jQuery( '.question_box' ).each( function() {
			if( ! jQuery( this ).hasClass( 'user_right' ) && ! jQuery( this ).hasClass( 'user_wrong' ) ) {
				jQuery( this ).addClass( 'ungraded' );
		 		if( jQuery( this ).hasClass( 'gap-fill' ) ) {
		 			var user_answer = jQuery( this ).find( '.user-answer .highlight' ).html();
			 		var correct_answer = jQuery( this ).find( '.correct-answer .highlight' ).html();
		 		} else {
			 		var user_answer = jQuery( this ).find( '.user-answer' ).html();
			 		var correct_answer = jQuery( this ).find( '.correct-answer' ).html();
		 		}

		 		if( user_answer == correct_answer ) {
		 			jQuery( this ).addClass( 'user_right' ).removeClass( 'user_wrong' ).removeClass( 'ungraded' );
		 			jQuery( this ).find( '.grading-mark.icon_right input' ).attr( 'checked', true );
		 			jQuery( this ).find( '.grading-mark.icon_wrong input' ).attr( 'checked', false );
		 		} else {
		 			if( jQuery( this ).hasClass( 'auto-grade' ) ) {
		 				jQuery( this ).addClass( 'user_wrong' ).removeClass( 'user_right' ).removeClass( 'ungraded' );
		 				jQuery( this ).find( '.grading-mark.icon_wrong input' ).attr( 'checked', true );
		 				jQuery( this ).find( '.grading-mark.icon_right input' ).attr( 'checked', false );
		 			} else {
		 				jQuery( this ).find( '.grading-mark.icon_wrong input' ).attr( 'checked', false );
						jQuery( this ).find( '.grading-mark.icon_right input' ).attr( 'checked', false );
						jQuery( this ).removeClass( 'user_wrong' ).removeClass( 'user_right' );
		 			}
		 		}
		 	}
	 	});
	 	jQuery.fn.calculateTotalGrade();
	}

	/**
	 * Resets all graded questions
	 * @param  obj	scope	Scope of questions to reset
	 * @return void
	 */
	jQuery.fn.resetGrades = function() {
		jQuery( '.question_box' ).find( '.grading-mark.icon_wrong input' ).attr( 'checked', false );
		jQuery( '.question_box' ).find( '.grading-mark.icon_right input' ).attr( 'checked', false );
		jQuery( '.question_box' ).removeClass( 'user_wrong' ).removeClass( 'user_right' ).removeClass( 'ungraded' );
		jQuery.fn.calculateTotalGrade();
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
		var courseId = jQuery( '#grading-course-options' ).val();
		var dataToPost = '';
	 	dataToPost += 'lesson_id' + '=' + lessonId;
	 	dataToPost += '&course_id' + '=' + courseId;
		// Perform the AJAX call to get the select box.
		jQuery.post(
			ajaxurl,
			{
				action : 'get_redirect_url',
				get_lessons_html_nonce : woo_localized_data.get_lessons_html_nonce,
				data : dataToPost
			},
			function( response ) {
				// Check for a response
				console.log(response);
				if ( '' != response ) {
					window.location = response;
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

	/**
	 * Grade Change Event.
	 *
	 * @since 1.3.0
	 * @access public
	 */
	jQuery( '.grading-mark' ).on( 'change', 'input', function() {
		if( this.value == 'right' ) {
			jQuery( '#' + this.name + '_box' ).addClass( 'user_right' ).removeClass( 'user_wrong' );
		} else {
			jQuery( '#' + this.name + '_box' ).addClass( 'user_wrong' ).removeClass( 'user_right' );
		}
		jQuery.fn.calculateTotalGrade();
	});

	/**
	 * Grade Reset Event.
	 *
	 * @since 1.3.0
	 * @access public
	 */
	jQuery( '.sensei-grading-main .buttons' ).on( 'click', '.reset-button', function() {
		jQuery.fn.resetGrades();
	});

	/**
	 * Auto grade event
	 *
	 * @since 1.3.0
	 * @access public
	 */
	jQuery( '.sensei-grading-main .buttons' ).on( 'click', '.autograde-button', function() {
		jQuery.fn.autoGrade();
	});

	/***************************************************************************************************
	 * 	4 - Load Chosen Dropdowns.
	 ***************************************************************************************************/

	// Grading Overview Drop Downs
	if ( jQuery( '#grading-course-options' ).exists() ) { jQuery( '#grading-course-options' ).chosen(); }
	if ( jQuery( '#grading-lesson-options' ).exists() ) { jQuery( '#grading-lesson-options' ).chosen(); }


});