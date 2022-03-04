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
import CoursePricingPromoSidebar from './course-pricing-promo-sidebar';
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

	const trackLinkClickCallback = ( event_name ) => ( e ) => {
		var properties = {
			course_status: e.target.dataset.courseStatus,
		};

		// Get course status from post state if it's available.
		if ( wp.data && wp.data.select( 'core/editor' ) ) {
			properties.course_status = wp.data
				.select( 'core/editor' )
				.getCurrentPostAttribute( 'status' );
		}

		sensei_log_event( event_name, properties );
	};

	// Log when the "Add Lesson" link is clicked.
	document
		.querySelector( 'a.add-course-lesson' )
		?.addEventListener(
			'click',
			trackLinkClickCallback( 'course_add_lesson_click' )
		);

	// Log when the "Edit Lesson" link is clicked.
	document
		.querySelector( 'a.edit-lesson-action' )
		?.addEventListener(
			'click',
			trackLinkClickCallback( 'course_edit_lesson_click' )
		);
} );

/**
 * Plugins
 */

/**
 * Filters the course pricing sidebar toggle.
 *
 * @since 4.1.0
 *
 * @hook  senseiCoursePricingHide     Hook used to hide course pricing promo sidebar.
 *
 * @param {boolean} hideCoursePricing Boolean value that defines if the course pricing promo sidebar should be hidden.
 * @return {boolean}                  Returns a boolean value that defines if the course pricing promo sidebar should be hidden.
 */
if ( ! applyFilters( 'senseiCoursePricingHide', false ) ) {
	registerPlugin( 'sensei-course-pricing-promo-sidebar', {
		render: CoursePricingPromoSidebar,
		icon: null,
	} );
}

/**
 * Filters the course access period display.
 *
 * @since 4.1.0
 *
 * @param {boolean} hideCourseAccessPeriod Whether to hide the access period.
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
