/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

jQuery( document ).ready( function ( $ ) {
	/***************************************************************************************************
	 * 	1 - Helper Functions.
	 ***************************************************************************************************/

	/**
	 * exists checks if selector exists
	 * @since  1.2.0
	 * @return {boolean} Whether the selector matches any elements.
	 */
	jQuery.fn.exists = function () {
		return this.length > 0;
	};

	/**
	 * Calculates the total grade based on the questions already graded
	 */
	jQuery.fn.calculateTotalGrade = function () {
		let questionId;
		let questionGrade;
		let totalGrade = 0;
		let totalGradedQuestions = 0;
		jQuery( '.question_box.user_right' ).each( function () {
			questionId = jQuery( this ).find( '.question_id' ).val();
			questionGrade = parseInt(
				jQuery( this )
					.find( '#question_' + questionId + '_grade' )
					.val()
			);
			totalGrade += questionGrade;
			totalGradedQuestions++;
		} );
		jQuery( '.question_box.user_wrong' ).each( function () {
			totalGradedQuestions++;
		} );

		jQuery( '#total_graded_questions' ).val( totalGradedQuestions );

		const totalQuestions = parseInt( jQuery( '#total_questions' ).val() );
		const quizGradeTotal = parseInt( jQuery( '#quiz_grade_total' ).val() );
		let percent = '0';

		if ( 0 < quizGradeTotal ) {
			percent = parseFloat(
				( totalGrade * 100 ) / quizGradeTotal
			).toFixed( 2 );
		}

		percent = percent.replace( '.00', '' );

		jQuery( '#total_grade' ).val( totalGrade );
		jQuery( '.total_grade_total' ).html( totalGrade );
		jQuery( '.total_grade_percent' ).html( percent );
		jQuery( '.quiz_grade_total' ).html( quizGradeTotal );

		if ( totalQuestions === totalGradedQuestions ) {
			jQuery( '#all_questions_graded' ).val( 'yes' );
			jQuery( '.grade-button' ).val( __( 'Grade', 'sensei-lms' ) );
		} else {
			jQuery( '#all_questions_graded' ).val( 'no' );
			jQuery( '.grade-button' ).val( __( 'Save', 'sensei-lms' ) );
		}
	};

	jQuery.fn.updateFeedback = function () {
		jQuery( '.question_box' ).each( function () {
			const questionId = jQuery( this ).find( '.question_id' ).val();
			const questionGrade = parseInt(
				jQuery( this )
					.find( '#question_' + questionId + '_grade' )
					.val()
			);

			const correctFeedback = jQuery( this ).find(
				'.answer-feedback-correct'
			);
			const incorrectFeedback = jQuery( this ).find(
				'.answer-feedback-incorrect'
			);

			correctFeedback.toggle( 0 < questionGrade );
			incorrectFeedback.toggle( ! questionGrade );
		} );
	};

	/**
	 * Automatically grades questions where possible
	 */
	$.fn.autoGrade = function () {
		$( '.question_box' ).each( function () {
			const $this = $( this );
			let allCorrect = false;

			// Only grade questions that haven't already been graded.
			if (
				! $this.hasClass( 'user_right' ) &&
				! $this.hasClass( 'user_wrong' ) &&
				! $this.hasClass( 'zero-graded' )
			) {
				let userAnswer, correctAnswer;

				$this.addClass( 'ungraded' );

				if ( $this.hasClass( 'gap-fill' ) ) {
					userAnswer = $this
						.find( '.user-answer' )
						.contents()
						.find( '.highlight' )
						.html();
					correctAnswer = $this
						.find( '.correct-answer .highlight' )
						.html();
				} else {
					userAnswer = $this
						.find( '.user-answer' )
						.contents()
						.find( 'body' )
						.map( function () {
							return this.innerHTML.trim();
						} )
						.toArray()
						.join( '<br>' );

					correctAnswer = $this.find( '.correct-answer' ).html();
				}

				userAnswer = userAnswer.trim();
				correctAnswer = correctAnswer.trim();

				// Auto-grading
				if ( $this.hasClass( 'auto-grade' ) ) {
					// Split answers to multiple choice questions into an array since there may be
					// multiple correct answers.
					if ( $this.hasClass( 'multiple-choice' ) ) {
						const userAnswers = userAnswer.split( '<br>' );
						const correctAnswers = correctAnswer.split( '<br>' );

						allCorrect = true;

						userAnswers.forEach( function ( answer ) {
							if ( -1 === $.inArray( answer, correctAnswers ) ) {
								allCorrect = false;
							}
						} );

						if (
							userAnswers.length !==
							correctAnswers.length - 1
						) {
							allCorrect = false;
						}
					} else {
						userAnswer = userAnswer.split( '<br>' )[ 0 ];
						correctAnswer = correctAnswer.split( '<br>' )[ 0 ];
					}

					if ( allCorrect || userAnswer === correctAnswer ) {
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
		$.fn.updateFeedback();
	};

	// Calculate total grade on page load to make sure everything is set up correctly
	jQuery.fn.autoGrade();

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
		jQuery.fn.updateFeedback();
	};

	jQuery.fn.getQueryVariable = function ( variable ) {
		const query = window.location.search.substring( 1 );
		const vars = query.split( '&' );
		for ( let i = 0; i < vars.length; i++ ) {
			const pair = vars[ i ].split( '=' );
			if ( pair[ 0 ] === variable ) {
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
		const courseId = jQuery( this ).val();
		jQuery.get(
			ajaxurl,
			{
				action: 'get_lessons_dropdown',
				course_id: courseId,
			},
			function ( response ) {
				// Check for a response
				if ( '' !== response ) {
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
		const lessonId = jQuery( this ).val();
		const courseId = jQuery( '#grading-course-options' ).val();
		const gradingView = jQuery.fn.getQueryVariable( 'view' );

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
				if ( '' !== response ) {
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
		if ( this.value === 'right' ) {
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
		jQuery.fn.updateFeedback();
	} );

	/**
	 * Grade value change event
	 *
	 * @since 1.4.0
	 * @access public
	 */
	jQuery( '.question-grade' ).on( 'change', '', function () {
		const grade = parseInt( jQuery( this ).val() );
		const questionLabel = this.id.replace( '_grade', '' );
		if ( grade > 0 ) {
			jQuery( '#' + questionLabel + '_box' )
				.addClass( 'user_right' )
				.removeClass( 'user_wrong' );
			jQuery(
				'#' +
					questionLabel +
					'_box .grading-mark input.' +
					questionLabel +
					'_right_option'
			).attr( 'checked', 'checked' );
			jQuery(
				'#' +
					questionLabel +
					'_box .grading-mark input.' +
					questionLabel +
					'_wrong_option'
			).attr( 'checked', false );
		} else {
			jQuery( '#' + questionLabel + '_box' )
				.addClass( 'user_wrong' )
				.removeClass( 'user_right' );
			jQuery(
				'#' +
					questionLabel +
					'_box .grading-mark input.' +
					questionLabel +
					'_wrong_option'
			).attr( 'checked', 'checked' );
			jQuery(
				'#' +
					questionLabel +
					'_box .grading-mark input.' +
					questionLabel +
					'_right_option'
			).attr( 'checked', false );
		}
		jQuery.fn.calculateTotalGrade();
		jQuery.fn.updateFeedback();
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

	if ( jQuery( '.sensei-grading-main' ).length ) {
		jQuery.fn.updateFeedback();
	}

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
