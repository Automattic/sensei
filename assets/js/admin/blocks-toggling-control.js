/**
 * WordPress dependencies
 */
import { select, dispatch, subscribe } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import onPostSave from '../../shared/helpers/on-post-save';

// Sensei blocks by post type.
const SENSEI_BLOCKS = {
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

// Metabox replacements.
const metaboxReplacements = {
	course: {
		'meta-box-course-lessons': [ SENSEI_BLOCKS.course.outline ],
		'meta-box-module_course_mb': [ SENSEI_BLOCKS.course.outline ],
		'meta-box-course-video': Object.values( SENSEI_BLOCKS.course ),
	},
	lesson: {
		'meta-box-lesson-info': [ SENSEI_BLOCKS.lesson.lessonProperties ],
	},
};

// WordPress data.
const blockEditorSelector = select( 'core/block-editor' );
const coreEditorSelector = select( 'core/editor' );
const editPostSelector = select( 'core/edit-post' );
const editPostDispatcher = dispatch( 'core/edit-post' );
const { createWarningNotice, removeNotice } = dispatch( 'core/notices' );

/**
 * Start blocks toggling control.
 * It controls the metaboxes and a notice if the page will
 * render differently (legacy template or blocks) after
 * saving the post.
 *
 * @param {string} postType Current post type.
 */
export const startBlocksTogglingControl = ( postType ) => {
	if ( ! blockEditorSelector ) {
		return;
	}

	let initialSenseiBlocksCount;
	let lastBlocks;

	subscribe( () => {
		// Set initial blocks.
		if (
			coreEditorSelector.isEditedPostDirty() &&
			undefined === initialSenseiBlocksCount
		) {
			updateInitialCount();
		}

		const newBlocks = select( 'core/editor' ).getBlocks();

		// Check if blocks were changed.
		if ( newBlocks !== lastBlocks ) {
			lastBlocks = newBlocks;
			toggleLegacyMetaboxes();

			if ( undefined !== initialSenseiBlocksCount ) {
				toggleLegacyOrBlocksNotice();
			}
		}
	} );

	/**
	 * Update initial blocks.
	 */
	const updateInitialCount = () => {
		initialSenseiBlocksCount = getBlocksCount(
			Object.values( SENSEI_BLOCKS[ postType ] )
		);
	};

	/**
	 * Update initial blocks on post save.
	 */
	onPostSave( () => {
		updateInitialCount();
		toggleLegacyOrBlocksNotice();
	} );

	/**
	 * Toggle metaboxes if a replacement block is present or not.
	 */
	const toggleLegacyMetaboxes = () => {
		Object.entries( metaboxReplacements[ postType ] ).forEach(
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
	 * Show a warning notice when changing to a state where it
	 * will start using the legacy template or the blocks.
	 */
	const toggleLegacyOrBlocksNotice = () => {
		const senseiBlocksCount = getBlocksCount(
			Object.values( SENSEI_BLOCKS[ postType ] )
		);

		if ( senseiBlocksCount > 0 ) {
			removeNotice( 'sensei-using-template' );

			if ( initialSenseiBlocksCount === 0 ) {
				const message = __(
					'This page contains a Sensei LMS block. When viewed, only the blocks will be displayed and the rest of the page will be empty.',
					'sensei-lms'
				);
				createWarningNotice( message, {
					id: 'sensei-using-blocks',
					isDismissible: true,
				} );
			} else {
				removeNotice( 'sensei-using-blocks' );
			}
		} else if ( senseiBlocksCount === 0 ) {
			removeNotice( 'sensei-using-blocks' );

			if ( initialSenseiBlocksCount > 0 ) {
				const message = __(
					'This page does not contain any Sensei LMS blocks. When viewed, it will display some details that cannot be easily customized. If you would like to have more control over the look and feel of this page, we recommend that you add some Sensei LMS blocks from the block inserter.',
					'sensei-lms'
				);
				createWarningNotice( message, {
					id: 'sensei-using-template',
					isDismissible: true,
				} );
			} else {
				removeNotice( 'sensei-using-template' );
			}
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
};
