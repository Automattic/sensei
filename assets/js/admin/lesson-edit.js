/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

/**
 * Internal dependencies
 */
import { startBlocksTogglingControl } from './blocks-toggling-control';

domReady( () => {
	startBlocksTogglingControl( 'lesson' );

	// Lessons Write Panel.
	const complexityOptionElements = jQuery( '#lesson-complexity-options' );
	if ( complexityOptionElements.length > 0 ) {
		complexityOptionElements.select2( { width: 'resolve' } );
	}

	const prerequisiteOptionElements = jQuery( '#lesson-prerequisite-options' );
	if ( prerequisiteOptionElements.length > 0 ) {
		prerequisiteOptionElements.select2( { width: 'resolve' } );
	}

	const courseOptionElements = jQuery( '#lesson-course-options' );
	if ( courseOptionElements.length > 0 ) {
		courseOptionElements.select2( { width: 'resolve' } );
	}

	const moduleOptionElements = jQuery( '#lesson-module-options' );
	if ( moduleOptionElements.length > 0 ) {
		moduleOptionElements.select2( { width: 'resolve' } );
	}

	// Refresh the prerequisite meta box when the course changes in order to get the relevant prerequisites.
	jQuery( '#lesson-course-options' ).on( 'change', function () {
		// Try to get the lesson ID from the wp data store. If not present, fallback to getting it from the DOM.
		const lessonId =
			wp.data.select( 'core/editor' )?.getCurrentPostId() ||
			jQuery( '#post_ID' ).val();
		const courseId = jQuery( this ).val();

		jQuery.get(
			ajaxurl,
			{
				action: 'get_prerequisite_meta_box_content',
				lesson_id: lessonId,
				course_id: courseId,
				security:
					window.sensei_lesson_metadata
						.get_prerequisite_meta_box_content_nonce,
			},
			function ( response ) {
				if ( '' !== response ) {
					// Replace the meta box and re-initialize select2.
					jQuery( '> .inside', '#lesson-prerequisite' ).html(
						response
					);
					jQuery( '#lesson-prerequisite-options' ).select2( {
						width: 'resolve',
					} );
				}
			}
		);
	} );
} );
