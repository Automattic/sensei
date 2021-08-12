jQuery( document ).ready( function ( $ ) {
	/***************************************************************************************************
	 * 	1 - Helper Functions.
	 ***************************************************************************************************/

	/**
	 * exists checks if selector exists
	 * @since  1.2.0
	 * @return boolean
	 */
	jQuery.fn.exists = function () {
		return this.length > 0;
	};

	/**
	 * Calculates the total grade based on the questions already graded
	 * @return void
	 */
	jQuery.fn.calculateTotalGrade = function () {
		var question_id;
		var question_grade;
		var total_grade = 0;
		var total_graded_questions = 0;
		jQuery( '.question_box.user_right' ).each( function () {
			question_id = jQuery( this ).find( '.question_id' ).val();
			question_grade = parseInt(
				jQuery( this )
					.find( '#question_' + question_id + '_grade' )
					.val()
			);
			total_grade += question_grade;
			total_graded_questions++;
		} );
		jQuery( '.question_box.user_wrong' ).each( function () {
			total_graded_questions++;
		} );

		jQuery( '#total_graded_questions' ).val( total_graded_questions );

		var total_questions = parseInt( jQuery( '#total_questions' ).val() );
		var quiz_grade_total = parseInt( jQuery( '#quiz_grade_total' ).val() );
		var percent = '0';

		if ( 0 < quiz_grade_total ) {
			percent = parseFloat(
				( total_grade * 100 ) / quiz_grade_total
			).toFixed( 2 );
		}

		percent = percent.replace( '.00', '' );

		jQuery( '#total_grade' ).val( total_grade );
		jQuery( '.total_grade_total' ).html( total_grade );
		jQuery( '.total_grade_percent' ).html( percent );
		jQuery( '.quiz_grade_total' ).html( quiz_grade_total );

		if ( total_questions == total_graded_questions ) {
			jQuery( '#all_questions_graded' ).val( 'yes' );
			jQuery( '.grade-button' ).val( 'Grade' );
		} else {
			jQuery( '#all_questions_graded' ).val( 'no' );
			jQuery( '.grade-button' ).val( 'Save' );
		}
	};

	/**
	 * Automatically grades questions where possible
	 * @return void
	 */
	$.fn.autoGrade = function () {
		$( '.question_box' ).each( function () {
			var $this = $( this );
			var all_correct = false;

			// Only grade questions that haven't already been graded.
			if (
				! $this.hasClass( 'user_right' ) &&
				! $this.hasClass( 'user_wrong' ) &&
				! $this.hasClass( 'zero-graded' )
			) {
				var user_answer, correct_answer;

				$this.addClass( 'ungraded' );

				if ( $this.hasClass( 'gap-fill' ) ) {
					user_answer = $this
						.find( '.user-answer .highlight' )
						.html();
					correct_answer = $this
						.find( '.correct-answer .highlight' )
						.html();
				} else {
					user_answer = $this.find( '.user-answer' ).html();
					correct_answer = $this.find( '.correct-answer' ).html();
				}

				user_answer = $.trim( user_answer );
				correct_answer = $.trim( correct_answer );

				// Auto-grading
				if ( $this.hasClass( 'auto-grade' ) ) {
					// Split answers to multiple choice questions into an array since there may be
					// multiple correct answers.
					if ( $this.hasClass( 'multiple-choice' ) ) {
						var user_answers = user_answer.split( '<br>' );
						var correct_answers = correct_answer.split( '<br>' );
						all_correct = true;

						user_answers.forEach( function ( user_answer ) {
							if (
								-1 === $.inArray( user_answer, correct_answers )
							) {
								all_correct = false;
							}
						} );

						if ( user_answers.length !== correct_answers.length ) {
							all_correct = false;
						}
					}

					if ( all_correct || user_answer === correct_answer ) {
						// Right answer
						$this
							.addClass( 'user_right' )
							.removeClass( 'user_wrong' )
							.removeClass( 'ungraded' );
						$this
							.find( '.grading-mark.icon_right input' )
							.attr( 'checked', true );
						$this
							.find( '.grading-mark.icon_wrong input' )
							.attr( 'checked', false );
						$this
							.find( 'input.question-grade' )
							.val(
								$this.find( 'input.question_total_grade' ).val()
							);
					} else {
						// Wrong answer
						$this
							.addClass( 'user_wrong' )
							.removeClass( 'user_right' )
							.removeClass( 'ungraded' );
						$this
							.find( '.grading-mark.icon_wrong input' )
							.attr( 'checked', true );
						$this
							.find( '.grading-mark.icon_right input' )
							.attr( 'checked', false );
						$this.find( 'input.question-grade' ).val( 0 );
					}
				} else {
					// Manual grading
					$this
						.find( '.grading-mark.icon_wrong input' )
						.attr( 'checked', false );
					$this
						.find( '.grading-mark.icon_right input' )
						.attr( 'checked', false );
					$this
						.removeClass( 'user_wrong' )
						.removeClass( 'user_right' );
				}
				// Question with a grade value of 0.
			} else if ( jQuery( this ).hasClass( 'zero-graded' ) ) {
				$this
					.find( '.grading-mark.icon_wrong input' )
					.attr( 'checked', false );
				$this
					.find( '.grading-mark.icon_right input' )
					.attr( 'checked', false );
				$this.find( 'input.question-grade' ).val( 0 );
			}
		} );

		$.fn.calculateTotalGrade();
	};

	/**
	 * Resets all graded questions.
	 */
	jQuery.fn.resetGrades = function () {
		jQuery( '.question_box' )
			.find( '.grading-mark.icon_wrong input' )
			.attr( 'checked', false );
		jQuery( '.question_box' )
			.find( '.grading-mark.icon_right input' )
			.attr( 'checked', false );
		jQuery( '.question_box' )
			.removeClass( 'user_wrong' )
			.removeClass( 'user_right' )
			.removeClass( 'ungraded' );
		jQuery( '.question-grade' ).val( '0' );
		jQuery.fn.calculateTotalGrade();
	};

	jQuery.fn.getQueryVariable = function ( variable ) {
		var query = window.location.search.substring( 1 );
		var vars = query.split( '&' );
		for ( var i = 0; i < vars.length; i++ ) {
			var pair = vars[ i ].split( '=' );
			if ( pair[ 0 ] == variable ) {
				return pair[ 1 ];
			}
		}
		return false;
	};

	/***************************************************************************************************
	 * 	2 - Grading Overview Functions.
	 ***************************************************************************************************/

	/**
	 * Course Change Event.
	 *
	 * @since 1.3.0
	 * @access public
	 */
	jQuery( '#grading-course-options' ).on( 'change', '', function () {
		// Populate the Lessons select box
		var courseId = jQuery( this ).val();
		jQuery.get(
			ajaxurl,
			{
				action: 'get_lessons_dropdown',
				course_id: courseId,
			},
			function ( response ) {
				// Check for a response
				if ( '' != response ) {
					// Empty the results div's
					jQuery( '#learners-to-grade' ).empty();
					jQuery( '#learners-graded' ).empty();
					// Populate the Lessons drop down
					jQuery( '#grading-lesson-options' )
						.empty()
						.append( response );
					// Add Chosen to the drop down
					if ( jQuery( '#grading-lesson-options' ).exists() ) {
						// Show the Lessons label
						jQuery( '#grading-lesson-options-label' ).show();
						jQuery( '#grading-lesson-options' ).trigger( 'change' );
					} // End If Statement
				} else {
					// Failed
				}
			}
		);
		return false;
	} );

	/**
	 * Lesson Change Event.
	 *
	 * @since 1.3.0
	 * @access public
	 */
	jQuery( '#grading-lesson-options' ).on( 'change', '', function () {
		// Populate the Lessons select box
		var lessonId = jQuery( this ).val();
		var courseId = jQuery( '#grading-course-options' ).val();
		var gradingView = jQuery.fn.getQueryVariable( 'view' );

		// Perform the AJAX call to get the select box.
		jQuery.get(
			ajaxurl,
			{
				action: 'get_redirect_url',
				course_id: courseId,
				lesson_id: lessonId,
				view: gradingView,
			},
			function ( response ) {
				// Check for a response
				if ( '' != response ) {
					window.location = response;
				} else {
					// Failed
				}
			}
		);
		return false;
	} );

	/***************************************************************************************************
	 * 	3 - Grading User Quiz Functions.
	 ***************************************************************************************************/

	/**
	 * Grade change event
	 *
	 * @since 1.3.0
	 * @access public
	 */
	jQuery( '.grading-mark' ).on( 'change', 'input', function () {
		if ( this.value == 'right' ) {
			jQuery( '#' + this.name + '_box' )
				.addClass( 'user_right' )
				.removeClass( 'user_wrong ungraded' );
			jQuery( '#' + this.name + '_box' )
				.find( 'input.question-grade' )
				.val(
					jQuery( '#' + this.name + '_box' )
						.find( 'input.question_total_grade' )
						.val()
				);
		} else {
			jQuery( '#' + this.name + '_box' )
				.addClass( 'user_wrong' )
				.removeClass( 'user_right ungraded' );
			jQuery( '#' + this.name + '_box' )
				.find( 'input.question-grade' )
				.val( 0 );
		}
		jQuery.fn.calculateTotalGrade();
	} );

	/**
	 * Grade value change event
	 *
	 * @since 1.4.0
	 * @access public
	 */
	jQuery( '.question-grade' ).on( 'change', '', function () {
		var grade = parseInt( jQuery( this ).val() );
		var question_label = this.id.replace( '_grade', '' );
		if ( grade > 0 ) {
			jQuery( '#' + question_label + '_box' )
				.addClass( 'user_right' )
				.removeClass( 'user_wrong' );
			jQuery(
				'#' +
					question_label +
					'_box .grading-mark input.' +
					question_label +
					'_right_option'
			).attr( 'checked', 'checked' );
			jQuery(
				'#' +
					question_label +
					'_box .grading-mark input.' +
					question_label +
					'_wrong_option'
			).attr( 'checked', false );
		} else {
			jQuery( '#' + question_label + '_box' )
				.addClass( 'user_wrong' )
				.removeClass( 'user_right' );
			jQuery(
				'#' +
					question_label +
					'_box .grading-mark input.' +
					question_label +
					'_wrong_option'
			).attr( 'checked', 'checked' );
			jQuery(
				'#' +
					question_label +
					'_box .grading-mark input.' +
					question_label +
					'_right_option'
			).attr( 'checked', false );
		}
		jQuery.fn.calculateTotalGrade();
	} );

	/**
	 * Grade reset event
	 *
	 * @since 1.3.0
	 * @access public
	 */
	jQuery( '.sensei-grading-main .buttons' ).on(
		'click',
		'.reset-button',
		function () {
			jQuery.fn.resetGrades();
		}
	);

	/**
	 * Auto grade event
	 *
	 * @since 1.3.0
	 * @access public
	 */
	jQuery( '.sensei-grading-main .buttons' ).on(
		'click',
		'.autograde-button',
		function () {
			// Toggle manual-grade questions to auto-grade for question types that are able to be
			// automatically graded, so that they will now be scored.
			$(
				'.boolean.manual-grade, .multiple-choice.manual-grade, .gap-fill.manual-grade'
			)
				.addClass( 'auto-grade' )
				.removeClass( 'manual-grade' );
			jQuery.fn.autoGrade();
		}
	);

	/***************************************************************************************************
	 * 	4 - Load Select2 Dropdowns.
	 ***************************************************************************************************/

	// Grading Overview Drop Downs
	if ( jQuery( '#grading-course-options' ).exists() ) {
		jQuery( '#grading-course-options' ).select2();
	}
	if ( jQuery( '#grading-lesson-options' ).exists() ) {
		jQuery( '#grading-lesson-options' ).select2();
	}
} );
