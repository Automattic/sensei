import { select, dispatch } from '@wordpress/data';

( () => {
	const COURSE_OUTLINE_NAME = 'sensei-lms/course-outline';
	const OTHER_COURSE_BLOCKS = [
		'sensei-lms/button-take-course',
		'sensei-lms/button-contact-teacher',
		'sensei-lms/course-progress',
	];

	const blockEditorSelector = select( 'core/block-editor' );
	const editPostSelector = select( 'core/edit-post' );
	const editPostDispatcher = dispatch( 'core/edit-post' );

	/**
	 * Toggle meta boxes depending on the blocks.
	 */
	window.sensei_toggle_legacy_metaboxes = () => {
		if ( ! blockEditorSelector ) {
			return;
		}

		const outlineBlockCount = blockEditorSelector.getGlobalBlockCount(
			COURSE_OUTLINE_NAME
		);
		const otherCourseBlocksCount = OTHER_COURSE_BLOCKS.reduce(
			( sum, blockName ) =>
				sum + blockEditorSelector.getGlobalBlockCount( blockName ),
			0
		);

		const legacyMetaboxes = [
			{ name: 'meta-box-course-lessons', deps: outlineBlockCount },
			{ name: 'meta-box-module_course_mb', deps: outlineBlockCount },
			{
				name: 'meta-box-course-video',
				deps: outlineBlockCount + otherCourseBlocksCount,
			},
		];

		legacyMetaboxes.forEach( ( { name, deps } ) => {
			const enable = deps === 0;
			if ( enable !== editPostSelector.isEditorPanelEnabled( name ) ) {
				editPostDispatcher.toggleEditorPanelEnabled( name );
			}
		} );

		// Prevent submit modules.
		document
			.querySelectorAll( '#module_course_mb input' )
			.forEach( ( input ) => {
				input.disabled = outlineBlockCount > 0;
			} );
	};
} )();

jQuery( document ).ready( function ( $ ) {
	window.sensei_toggle_legacy_metaboxes();

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
