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
		featuredVideo: 'sensei-lms/featured-video',
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

	let lastBlocks;

	editorLifecycle( {
		subscribeListener: () => {
			const newBlocks = blockEditorSelector.getBlocks();

			// Check if blocks were changed.
			if ( newBlocks !== lastBlocks ) {
				lastBlocks = newBlocks;
				toggleLegacyMetaboxes();
				toggleLegacyOrBlocksNotice();
			}
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
		const courseThemeEnabled = window?.sensei?.courseThemeEnabled;

		if ( withSenseiBlocks || courseThemeEnabled ) {
			removeNotice( 'sensei-using-template' );
		} else {
			createWarningNotice(
				__(
					"It looks like this course page doesn't have any Sensei blocks. This means that content will be handled by custom templates.",
					'sensei-lms'
				),
				{
					id: 'sensei-using-template',
					isDismissible: true,
					actions: [
						{
							label: __( 'Learn more', 'sensei-lms' ),
							url:
								'https://senseilms.com/documentation/course-page-blocks/',
						},
					],
				}
			);
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
