/**
 * WordPress dependencies
 */
import { select, dispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';

// Legacy metaboxes toggle control.
( () => {
	const BLOCKS = {
		course: {
			outline: 'sensei-lms/course-outline',
			takeCourse: 'sensei-lms/button-take-course',
			contactTeacher: 'sensei-lms/button-contact-teacher',
			courseProgress: 'sensei-lms/course-progress',
		},
		lesson: {
			lessonActions: 'sensei-lms/lesson-actions',
			lessonProperties: 'sensei-lms/lesson-properties',
			contactTeacher: 'sensei-lms/button-contact-teacher',
		},
	};

	const legacyMetaboxesDependencies = {
		course: {
			'meta-box-course-lessons': [ BLOCKS.course.outline ],
			'meta-box-module_course_mb': [ BLOCKS.course.outline ],
			'meta-box-course-video': Object.values( BLOCKS.course ),
		},
		lesson: {
			'meta-box-lesson-info': [ BLOCKS.lesson.lessonProperties ],
		},
	};

	// WordPress data.
	const blockEditorSelector = select( 'core/block-editor' );
	const editPostSelector = select( 'core/edit-post' );
	const editPostDispatcher = dispatch( 'core/edit-post' );
	const { createWarningNotice, removeNotice } = dispatch( 'core/notices' );

	/**
	 * Toggle meta boxes depending on the blocks.
	 *
	 * @param {string} postType Post type.
	 * @param {string} action   Action which is calling the toggle: `add`, `remove` or `pageload`.
	 */
	window.sensei_toggle_legacy_metaboxes = ( postType, action ) => {
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

		toggleLegacyOrBlocksNotice( postType, action );

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
	 * Show a warning notice when changing to a state where it uses the legacy template
	 * or the blocks.
	 *
	 * @param {string} postType Post type.
	 * @param {string} action   Action which is calling the toggle: `add`, `remove` or `pageload`.
	 */
	const toggleLegacyOrBlocksNotice = ( postType, action ) => {
		const allBlocksCount = getBlocksCount(
			Object.values( BLOCKS[ postType ] )
		);

		if ( allBlocksCount === 1 && action === 'add' ) {
			const message = sprintf(
				/* translators: Post type. */
				__(
					'When you add at least one %s block to the editor, the frontend page stops loading the default template. It means you will see only the blocks you added in the frontend.',
					'sensei-lms'
				),
				postType
			);
			removeNotice( 'sensei-using-template' );
			createWarningNotice( message, {
				id: 'sensei-using-blocks',
				isDismissible: true,
			} );
		} else if ( allBlocksCount === 0 && action === 'remove' ) {
			const message = sprintf(
				/* translators: Post type. */
				__(
					'When you remove all the %s blocks from the editor, your page loads the default template. It means you will see a default page structure in the frontend.',
					'sensei-lms'
				),
				postType
			);
			removeNotice( 'sensei-using-blocks' );
			createWarningNotice( message, {
				id: 'sensei-using-template',
				isDismissible: true,
			} );
		}
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
