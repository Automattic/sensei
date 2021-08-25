/**
 * WordPress dependencies
 */
import { select, subscribe } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import domReady from '@wordpress/dom-ready';

/**
 * Internal dependencies
 */
import { startBlocksTogglingControl } from './blocks-toggling-control';

( () => {
	const editPostSelector = select( 'core/edit-post' );
	const teacherIdSelect = document.getElementsByName(
		'sensei-course-teacher-author'
	);

	if ( editPostSelector && teacherIdSelect.length ) {
		let isSavingMetaboxes = false;

		subscribe( () => {
			if ( editPostSelector.isSavingMetaBoxes() !== isSavingMetaboxes ) {
				isSavingMetaboxes = editPostSelector.isSavingMetaBoxes();

				if ( ! isSavingMetaboxes ) {
					const currentTeacherId = teacherIdSelect[ 0 ].value;
					if ( currentTeacherId ) {
						document.getElementsByName(
							'post_author_override'
						)[ 0 ].value = currentTeacherId;
					}
				}
			}
		} );
	}
} )();

domReady( () => {
	startBlocksTogglingControl( 'course' );

	jQuery( '#course-prerequisite-options' ).select2( { width: '100%' } );

	function trackLinkClickCallback( event_name ) {
		return function () {
			var properties = {
				course_status: jQuery( this ).data( 'course-status' ),
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
	jQuery( 'a.add-course-lesson' ).click(
		trackLinkClickCallback( 'course_add_lesson_click' )
	);

	// Log when the "Edit Lesson" link is clicked.
	jQuery( 'a.edit-lesson-action' ).click(
		trackLinkClickCallback( 'course_edit_lesson_click' )
	);
} );
