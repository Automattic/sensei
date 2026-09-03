/**
 * Lesson bulk edit screen save functionality
 */

jQuery( function ( $ ) {
	$( '#the-list' ).on( 'click', '#bulk-edit #bulk_edit ', function () {
		// define the bulk edit row
		const $bulk_row = $( '#bulk-edit' );

		// get the selected post ids that are being edited
		const postIds = new Array();
		$bulk_row.find( '#bulk-titles-list button' ).each( function () {
			postIds.push( $( this ).attr( 'id' ).replace( /^(_)/i, '' ) );
		} );

		// get the data:

		//security as the wordpress nonce
		const nonceVal = $( 'input[name="_edit_lessons_nonce"]' ).val();

		// selected course value
		const newCourse = $bulk_row.find( '#sensei-edit-lesson-course' ).val();

		// lesson complexity value
		const newComplexity = $bulk_row
			.find( '#sensei-edit-lesson-complexity' )
			.val();

		//
		//Quiz specific
		//

		// Quiz Pass required for completion
		const newPassRequired = $bulk_row
			.find( '#sensei-edit-lesson-pass-required' )
			.val();

		// Quiz Pass percentage
		const newPassPercentage = $bulk_row
			.find( '#sensei-edit-quiz-pass-percentage' )
			.val();

		// Quiz Pass percentage
		const newEnableQuizReset = $bulk_row
			.find( '#sensei-edit-enable-quiz-reset' )
			.val();

		// Quiz number of questions to show
		const newShowQuestions = $bulk_row
			.find( '#sensei-edit-show-questions' )
			.val();

		// Quiz Random Question Order
		const newRandomQuestionOrder = $bulk_row
			.find( '#sensei-edit-random-question-order' )
			.val();

		// Quiz Grade Type
		const newQuizGradeType = $bulk_row
			.find( '#sensei-edit-quiz-grade-type' )
			.val();

		// save the data
		$.ajax( {
			url: ajaxurl, // this is a variable that WordPress has already defined for us
			type: 'POST',
			async: false,
			cache: false,
			data: {
				action: 'save_bulk_edit_book', // this is the name of our WP AJAX function that we'll set up next
				security: nonceVal,

				// sending the field values
				sensei_edit_lesson_course: newCourse,
				sensei_edit_complexity: newComplexity,
				sensei_edit_pass_required: newPassRequired,
				sensei_edit_pass_percentage: newPassPercentage,
				sensei_edit_enable_quiz_reset: newEnableQuizReset,
				sensei_edit_show_questions: newShowQuestions,
				sensei_edit_random_question_order: newRandomQuestionOrder,
				sensei_edit_quiz_grade_type: newQuizGradeType,

				// post ids to apply the changes to
				post_ids: postIds,
			},
		} );
	} );
} );
