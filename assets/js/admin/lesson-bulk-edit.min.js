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

        // get the data
        var newCourse = $bulk_row.find( '#sensei-edit-lesson-course' ).val();
        var nonceVal = $('input[name="_edit_lessons_nonce"]').val();
        // save the data
        $.ajax({
            url: ajaxurl, // this is a variable that WordPress has already defined for us
            type: 'POST',
            async: false,
            cache: false,
            data: {
                action: 'save_bulk_edit_book', // this is the name of our WP AJAX function that we'll set up next
                security: nonceVal,
                sensei_edit_lesson_course: newCourse,
                post_ids: postIds, // post ids to apply the changes to
            }
        });
    });
})(jQuery);