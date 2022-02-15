/**
 * WordPress dependencies
 */
import { select, subscribe } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import domReady from '@wordpress/dom-ready';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { startBlocksTogglingControl } from './blocks-toggling-control';
import CourseTheme from './course-theme';
import CourseVideoSidebar from './course-video-sidebar';
import CourseAccessPeriodPromoSidebar from './course-access-period-promo-sidebar';

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

/**
 * Plugins
 */

/**
 * Filters the course access period display.
 *
 * @since 4.1.0
 *
 * @hook senseiCourseAccessPeriodHide
 * @param {boolean} $hideCourseAccessPeriod Whether to hide the access period.
 * @return {boolean} Whether to hide the access period.
 */
if ( ! applyFilters( 'senseiCourseAccessPeriodHide', false ) ) {
	registerPlugin( 'sensei-course-access-period-promo-plugin', {
		render: CourseAccessPeriodPromoSidebar,
		icon: null,
	} );
}

registerPlugin( 'sensei-course-theme-plugin', {
	render: CourseTheme,
	icon: null,
} );

registerPlugin( 'sensei-course-video-progression-plugin', {
	render: CourseVideoSidebar,
	icon: null,
} );
