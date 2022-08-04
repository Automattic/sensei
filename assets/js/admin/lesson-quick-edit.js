( function ( $ ) {
	// we create a copy of the WP inline edit post function
	var $wp_inline_edit = window.inlineEditPost.edit;
	// and then we overwrite the function with our own code
	window.inlineEditPost.edit = function ( id ) {
		// "call" the original WP edit function
		// we don't want to leave WordPress hanging
		$wp_inline_edit.apply( this, arguments );

		// now we take care of our business

		// get the post ID
		var postId = 0;
		if ( id instanceof Element ) {
			postId = parseInt( this.getId( id ) );
		}

		if ( postId > 0 ) {
			// define the edit row
			var editRow = $( '#edit-' + postId );
			var senseiFieldValues = window[ 'sensei_quick_edit_' + postId ];

			//on the save button click, set senseiFieldValues to the values user entered in the form fields
			editRow.find( '.save' ).on( 'click', function () {
				senseiFieldValues.lesson_course = $(
					'#sensei-edit-lesson-course'
				).val();
				senseiFieldValues.lesson_complexity = $(
					'#sensei-edit-lesson-complexity'
				).val();
				senseiFieldValues.pass_required = $(
					'#sensei-edit-lesson-pass-required'
				).val();
				senseiFieldValues.quiz_passmark = $(
					'#sensei-edit-quiz-pass-percentage'
				).val();
				senseiFieldValues.enable_quiz_reset = $(
					'#sensei-edit-enable-quiz-reset'
				).val();
				senseiFieldValues.show_questions = $(
					'#sensei-edit-show-questions'
				).val();
				senseiFieldValues.random_question_order = $(
					'#sensei-edit-random-question-order'
				).val();
				senseiFieldValues.quiz_grade_type = $(
					'#sensei-edit-quiz-grade-type'
				).val();
			} );

			// populate the data
			//data is localized in sensei_quick_edit object
			$(
				':input[name="lesson_course"] option[value="' +
					senseiFieldValues.lesson_course +
					'"] ',
				editRow
			).attr( 'selected', true );
			$(
				':input[name="lesson_complexity"] option[value="' +
					senseiFieldValues.lesson_complexity +
					'"] ',
				editRow
			).attr( 'selected', true );
			if (
				'on' == senseiFieldValues.pass_required ||
				'1' == senseiFieldValues.pass_required
			) {
				senseiFieldValues.pass_required = 1;
			} else {
				senseiFieldValues.pass_required = 0;
			}
			$(
				':input[name="pass_required"] option[value="' +
					senseiFieldValues.pass_required +
					'"] ',
				editRow
			).attr( 'selected', true );
			$( ':input[name="quiz_passmark"]', editRow ).val(
				senseiFieldValues.quiz_passmark
			);

			if (
				'on' == senseiFieldValues.enable_quiz_reset ||
				'1' == senseiFieldValues.enable_quiz_reset
			) {
				senseiFieldValues.enable_quiz_reset = 1;
			} else {
				senseiFieldValues.enable_quiz_reset = 0;
			}
			$(
				':input[name="enable_quiz_reset"] option[value="' +
					senseiFieldValues.enable_quiz_reset +
					'"] ',
				editRow
			).attr( 'selected', true );

			if (
				'auto' === senseiFieldValues.quiz_grade_type ||
				'1' === senseiFieldValues.quiz_grade_type
			) {
				senseiFieldValues.quiz_grade_type = 1;
			} else {
				senseiFieldValues.quiz_grade_type = 0;
			}
			$(
				':input[name="quiz_grade_type"] option[value="' +
					senseiFieldValues.quiz_grade_type +
					'"] ',
				editRow
			).attr( 'selected', true );

			if (
				'yes' == senseiFieldValues.random_question_order ||
				'1' == senseiFieldValues.random_question_order
			) {
				senseiFieldValues.random_question_order = 1;
			} else {
				senseiFieldValues.random_question_order = 0;
			}
			$(
				':input[name="random_question_order"] option[value="' +
					senseiFieldValues.random_question_order +
					'"] ',
				editRow
			).attr( 'selected', true );

			$( ':input[name="show_questions"]', editRow ).val(
				senseiFieldValues.show_questions
			);
		}
	};
} )( jQuery );
