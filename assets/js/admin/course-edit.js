/**
 * It enables or disables the legacy meta boxes.
 *
 * @param {boolean} enable Whether enable or disable.
 */
window.sensei_toggleLegacyMetaboxes = ( enable ) => {
	// Side-effect - Prevent submit modules.
	document
		.querySelectorAll( '#module_course_mb input' )
		.forEach( ( input ) => {
			input.disabled = ! enable;
		} );

	const editPostSelector = wp.data.select( 'core/edit-post' );
	const editPostDispatcher = wp.data.dispatch( 'core/edit-post' );

	if (
		enable !==
		editPostSelector.isEditorPanelEnabled( 'meta-box-course-lessons' )
	) {
		editPostDispatcher.toggleEditorPanelEnabled(
			'meta-box-course-lessons'
		);
	}

	if (
		enable !==
		editPostSelector.isEditorPanelEnabled( 'meta-box-module_course_mb' )
	) {
		editPostDispatcher.toggleEditorPanelEnabled(
			'meta-box-module_course_mb'
		);
	}
};

jQuery( document ).ready( function ( $ ) {
	window.sensei_toggleLegacyMetaboxes( true );

	$( '#course-prerequisite-options' ).select2( { width: '100%' } );

	function trackLinkClickCallback( event_name ) {
		return function () {
			var properties = {
				course_status: $( this ).data( 'course-status' ),
			};

			// Get course status from post state if it's available.
			if ( wp.data && wp.data.select( 'core/editor' ) ) {
				properties.course_status = wp.data
					.select( 'core/editor' )
					.getCurrentPostAttribute( 'status' );
			}

			sensei_log_event( event_name, properties );
		};
	}

	// Log when the "Add Lesson" link is clicked.
	$( 'a.add-course-lesson' ).click(
		trackLinkClickCallback( 'course_add_lesson_click' )
	);

	// Log when the "Edit Lesson" link is clicked.
	$( 'a.edit-lesson-action' ).click(
		trackLinkClickCallback( 'course_edit_lesson_click' )
	);
} );
