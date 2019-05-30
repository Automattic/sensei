jQuery( document ).ready( function( $ ) {
	// Log when the "Add Lesson" link is clicked.
	$( 'a.add-course-lesson' ).click( function() {
		var properties = {
			course_status: $( this ).data( 'course-status' ),
		};

		// Get course status from post state if it's available.
		if ( wp.data && wp.data.select( 'core/editor' ) ) {
			properties.course_status = wp.data.select( 'core/editor' ).getCurrentPostAttribute( 'status' );
		}

		sensei_log_event( 'course_add_lesson_click', properties );
	} );
} );
