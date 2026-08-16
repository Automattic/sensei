/**
 * WordPress dependencies
 */
import { select, dispatch } from '@wordpress/data';
import { store as editPostStore } from '@wordpress/edit-post';
import { store as editorStore } from '@wordpress/editor';

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
const editorSelector = select( editorStore );
const editorDispatcher = dispatch( editorStore );
const editPostSelector = select( editPostStore );
const editPostDispatcher = dispatch( editPostStore );

const isEditorPanelEnabled = editorSelector.isEditorPanelEnabled
	? editorSelector.isEditorPanelEnabled
	: editPostSelector.isEditorPanelEnabled;

const toggleEditorPanelEnabled = editorDispatcher.toggleEditorPanelEnabled
	? editorDispatcher.toggleEditorPanelEnabled
	: editPostDispatcher.toggleEditorPanelEnabled;

/**
 * Check whether a block tree contains at least one of the given block types.
 *
 * @param {string[]} blocksToFind Block types to find.
 * @param {Object[]} blocks       Blocks to search.
 *
 * @return {boolean} Whether the block tree contains a matching block.
 */
export const hasSomeBlocks = ( blocksToFind, blocks = [] ) =>
	blocks.some(
		( block ) =>
			blocksToFind.includes( block.name ) ||
			hasSomeBlocks( blocksToFind, block.innerBlocks ?? [] )
	);

/**
 * Start blocks toggling control.
 * It controls the metaboxes based on the blocks in the editor.
 *
 * @param {string} postType Current post type.
 */
export const startBlocksTogglingControl = ( postType ) => {
	if ( ! editorSelector ) {
		return;
	}

	let lastBlocks;

	editorLifecycle( {
		subscribeListener: () => {
			const newBlocks = editorSelector.getEditorBlocks();

			// Check if blocks were changed.
			if ( newBlocks !== lastBlocks ) {
				lastBlocks = newBlocks;
				toggleLegacyMetaboxes();
			}
		},
	} );

	/**
	 * Toggle metaboxes if a replacement block is present or not.
	 */
	const toggleLegacyMetaboxes = () => {
		const blocks = editorSelector.getEditorBlocks();

		Object.entries( metaboxReplacements[ postType ] ).forEach(
			( [ metaboxName, blockDeps ] ) => {
				const enable = ! hasSomeBlocks( blockDeps, blocks );
				if ( enable !== isEditorPanelEnabled( metaboxName ) ) {
					toggleEditorPanelEnabled( metaboxName );
				}
			}
		);

		// Prevent submit course modules.
		document
			.querySelectorAll( '#module_course_mb input' )
			.forEach( ( input ) => {
				input.disabled = ! isEditorPanelEnabled(
					'meta-box-module_course_mb'
				);
			} );

		// Don't submit lesson length and complexity values in metaboxes.
		document
			.querySelectorAll( '#lesson-info input, #lesson-info select' )
			.forEach( ( input ) => {
				input.disabled = ! isEditorPanelEnabled(
					'meta-box-lesson-info'
				);
			} );
	};

	toggleLegacyMetaboxes();
};
