/**
 * Lesson bulk edit screen save functionality
 */

(function($) {

    // Create a copy of the WP inline edit post function
    var $wp_inline_edit = inlineEditPost.edit;

    $( '#bulk_edit' ).live( 'click a#bulk_edit', function( e ) {
        // define the bulk edit row
        var $bulk_row = $( '#bulk-edit' );

        // get the selected post ids that are being edited
        var postIds = new Array();
        $bulk_row.find( '#bulk-titles' ).children().each( function() {
            postIds.push( $( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
        });

        // get the data:

        //security as the wordpress nonce
        var nonceVal = $('input[name="_edit_lessons_nonce"]').val();

        // selected course value
        var newCourse = $bulk_row.find( '#sensei-edit-lesson-course' ).val();

        // lesson complexity value
        var newComplexity = $bulk_row.find( '#sensei-edit-lesson-complexity' ).val();

        //
        //Quiz specific
        //

        // Quiz Pass required for completion
        var newPassRequired = $bulk_row.find( '#sensei-edit-lesson-pass-required' ).val();

        // Quiz Pass percentage
        var newPassPercentage = $bulk_row.find( '#sensei-edit-quiz-pass-percentage' ).val();

        // Quiz Pass percentage
        var newEnableQuizReset = $bulk_row.find( '#sensei-edit-enable-quiz-reset' ).val();

        // save the data
        $.ajax({
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
                sensei_edit_enable_quiz_reset:newEnableQuizReset,

                // post ids to apply the changes to
                post_ids: postIds
            }
        });
    });
})(jQuery);