/**
 * WordPress dependencies
 */
import { select, dispatch } from '@wordpress/data';

// Legacy metaboxes toggle control.
( () => {
	const COURSE_BLOCKS = {
		outline: 'sensei-lms/course-outline',
		takeCourse: 'sensei-lms/button-take-course',
		contactTeacher: 'sensei-lms/button-contact-teacher',
		courseProgress: 'sensei-lms/course-progress',
	};

	const LESSON_BLOCKS = {
		lessonActions: 'sensei-lms/lesson-actions',
		lessonProperties: 'sensei-lms/lesson-properties',
		contactTeacher: 'sensei-lms/button-contact-teacher',
	};

	const legacyMetaboxesDependencies = {
		course: {
			'meta-box-course-lessons': [ COURSE_BLOCKS.outline ],
			'meta-box-module_course_mb': [ COURSE_BLOCKS.outline ],
			'meta-box-course-video': Object.values( COURSE_BLOCKS ),
		},
		lesson: {
			'meta-box-lesson-info': [ LESSON_BLOCKS.lessonProperties ],
		},
	};

	// WordPress data.
	const blockEditorSelector = select( 'core/block-editor' );
	const editPostSelector = select( 'core/edit-post' );
	const editPostDispatcher = dispatch( 'core/edit-post' );

	/**
	 * Toggle meta boxes depending on the blocks.
	 *
	 * @param {string} postType Post type.
	 */
	window.sensei_toggle_legacy_metaboxes = ( postType ) => {
		if ( ! blockEditorSelector ) {
			return;
		}

		Object.entries( legacyMetaboxesDependencies[ postType ] ).forEach(
			( [ metaboxName, blockDeps ] ) => {
				const enable = getBlocksCount( blockDeps ) === 0;
				if (
					enable !==
					editPostSelector.isEditorPanelEnabled( metaboxName )
				) {
					editPostDispatcher.toggleEditorPanelEnabled( metaboxName );
				}
			}
		);

		// Prevent submit course modules.
		document
			.querySelectorAll( '#module_course_mb input' )
			.forEach( ( input ) => {
				input.disabled = ! editPostSelector.isEditorPanelEnabled(
					'meta-box-module_course_mb'
				);
			} );

		// Don't submit lesson length and complexity values in metaboxes.
		document
			.querySelectorAll( '#lesson-info input, #lesson-info select' )
			.forEach( ( input ) => {
				input.disabled = ! editPostSelector.isEditorPanelEnabled(
					'meta-box-lesson-info'
				);
			} );
	};

	/**
	 * Get blocks count.
	 *
	 * @param {string[]} blocks Blocks to count.
	 *
	 * @return {number} Number of blocks found.
	 */
	const getBlocksCount = ( blocks ) =>
		blocks.reduce(
			( sum, blockName ) =>
				sum + blockEditorSelector.getGlobalBlockCount( blockName ),
			0
		);
} )();
