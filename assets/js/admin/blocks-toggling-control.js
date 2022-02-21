/**
 * WordPress dependencies
 */
import { select, dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import editorLifecycle from '../../shared/helpers/editor-lifecycle';

// Sensei blocks by post type.
const SENSEI_BLOCKS = {
	course: {
		outline: 'sensei-lms/course-outline',
		takeCourse: 'sensei-lms/button-take-course',
		contactTeacher: 'sensei-lms/button-contact-teacher',
		courseProgress: 'sensei-lms/course-progress',
		viewResults: 'sensei-lms/button-view-results',
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

	const { createWarningNotice, removeNotice } = dispatch( 'core/notices' );

	let initialWithSenseiBlocks; // Whether initial state has Sensei Blocks.
	let previousWithSenseiBlocks; // Whether previous state has Sensei Blocks.
	let lastBlocks;

	editorLifecycle( {
		subscribeListener: () => {
			const newBlocks = blockEditorSelector.getBlocks();

			// Check if blocks were changed.
			if ( newBlocks !== lastBlocks ) {
				lastBlocks = newBlocks;
				toggleLegacyMetaboxes();

				previousWithSenseiBlocks = hasSenseiBlocks();

				if ( undefined !== initialWithSenseiBlocks ) {
					toggleLegacyOrBlocksNotice();
				}
			}
		},
		onSetDirty: () => {
			// Set initial blocks state.
			if (
				coreEditorSelector.isEditedPostDirty() &&
				undefined === initialWithSenseiBlocks
			) {
				// If it will fill the template (needs_template is true),
				// we consider that it has Sensei blocks initially.
				initialWithSenseiBlocks =
					coreEditorSelector.getCurrentPostAttribute( 'meta' )
						?._needs_template || previousWithSenseiBlocks;
			}
		},
		onSave: () => {
			// Update initial blocks state on save.
			initialWithSenseiBlocks = hasSenseiBlocks();
			toggleLegacyOrBlocksNotice();
		},
	} );

	/**
	 * Check whether it has Sensei blocks.
	 */
	const hasSenseiBlocks = () =>
		hasSomeBlocks( Object.values( SENSEI_BLOCKS[ postType ] ) );

	/**
	 * Toggle metaboxes if a replacement block is present or not.
	 */
	const toggleLegacyMetaboxes = () => {
		Object.entries( metaboxReplacements[ postType ] ).forEach(
			( [ metaboxName, blockDeps ] ) => {
				const enable = ! hasSomeBlocks( blockDeps );
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
		const withSenseiBlocks = hasSenseiBlocks();

		const noticeOptions = {
			isDismissible: true,
			actions: [
				{
					label: __( 'Learn more', 'sensei-lms' ),
					url:
						'https://senseilms.com/documentation/course-page-blocks/',
				},
			],
		};

		if ( withSenseiBlocks ) {
			removeNotice( 'sensei-using-template' );

			if ( ! initialWithSenseiBlocks ) {
				const message = __(
					"You've just added your first Sensei block. This will change how your course page appears. Be sure to preview your page before saving changes.",
					'sensei-lms'
				);
				createWarningNotice( message, {
					id: 'sensei-using-blocks',
					...noticeOptions,
				} );
			} else {
				removeNotice( 'sensei-using-blocks' );
			}
		} else {
			removeNotice( 'sensei-using-blocks' );

			if ( initialWithSenseiBlocks ) {
				const message = __(
					'Are you sure you want to remove all Sensei blocks? This will change how your course page appears. Be sure to preview your page before saving changes.',
					'sensei-lms'
				);
				createWarningNotice( message, {
					id: 'sensei-using-template',
					...noticeOptions,
				} );
			} else {
				removeNotice( 'sensei-using-template' );
			}
		}
	};

	/**
	 * Check whether it has at least one block.
	 *
	 * @param {string[]} blocks Blocks to check.
	 *
	 * @return {boolean} Whether it has at least one block.
	 */
	const hasSomeBlocks = ( blocks ) =>
		blocks.some(
			( blockName ) =>
				blockEditorSelector.getGlobalBlockCount( blockName ) > 0
		);
};
