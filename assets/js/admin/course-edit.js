/**
 * WordPress dependencies
 */
import { select, subscribe } from '@wordpress/data';
import domReady from '@wordpress/dom-ready';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import { startBlocksTogglingControl } from './blocks-toggling-control';
import {
	extractStructure,
	getFirstBlockByName,
} from '../../blocks/course-outline/data';
import {
	CourseSidebar,
	SenseiSettingsDocumentSidebar,
	pluginSidebarHandle,
	pluginDocumentHandle,
} from './course-settings-plugin-sidebar';

( () => {
	const editPostSelector = select( 'core/edit-post' );
	const coreEditor = select( 'core/editor' );
	const teacherIdSelect = document.getElementsByName(
		'sensei-course-teacher-author'
	);
	const slugBearer = document.getElementsByName(
		'course_module_custom_slugs'
	);
	if ( editPostSelector && teacherIdSelect.length ) {
		let isSavingMetaboxes = false;

		subscribe( () => {
			const isSavingPost =
				coreEditor.isSavingPost() && ! coreEditor.isAutosavingPost();
			if (
				isSavingPost &&
				! editPostSelector.isSavingMetaBoxes() &&
				slugBearer
			) {
				const outlineBlock = getFirstBlockByName(
					'sensei-lms/course-outline',
					select( 'core/block-editor' ).getBlocks()
				);
				const moduleSlugs =
					outlineBlock &&
					extractStructure( outlineBlock.innerBlocks )
						.filter( ( block ) => block.slug )
						.map( ( block ) => block.slug );
				slugBearer[ 0 ].value = JSON.stringify( moduleSlugs );
			}
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

registerPlugin( pluginSidebarHandle, {
	render: CourseSidebar,
} );

registerPlugin( pluginDocumentHandle, {
	render: SenseiSettingsDocumentSidebar,
	icon: null,
} );
