import { apiFetch, controls as dataControls } from '@wordpress/data-controls';
import { dispatch, registerStore, select, subscribe } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { isEqual } from 'lodash';

import { createReducerFromActionMap } from '../../shared/data/store-helpers';
import {
	syncStructureToBlocks,
	extractStructure,
	getFirstBlockByName,
} from './data';

const DEFAULT_STATE = {
	serverStructure: null,
	isSavingStructure: false,
	hasStructureUpdate: false,
};

const getEditorOutlineBlock = () =>
	getFirstBlockByName(
		'sensei-lms/course-outline',
		select( 'core/block-editor' ).getBlocks()
	);

const getEditorOutlineStructure = () => {
	const outlineBlock = getEditorOutlineBlock();

	if ( ! outlineBlock ) {
		return null;
	}

	return extractStructure( outlineBlock.innerBlocks );
};

const actions = {
	/**
	 * Fetch course structure data from REST API.
	 */
	*fetchCourseStructure() {
		const courseId = yield select( 'core/editor' ).getCurrentPostId();
		const result = yield apiFetch( {
			path: `/sensei-internal/v1/course-structure/${ courseId }?context=edit`,
		} );
		yield actions.setStructure( result );
	},

	/**
	 * Persist editor's course structure to the REST API.
	 *
	 * @param {Array} editorStructure
	 */
	*saveStructure( editorStructure ) {
		yield { type: 'SAVING', isSavingStructure: true };
		const courseId = yield select( 'core/editor' ).getCurrentPostId();

		try {
			const result = yield apiFetch( {
				path: `/sensei-internal/v1/course-structure/${ courseId }`,
				method: 'POST',
				data: { structure: editorStructure },
			} );
			yield actions.setStructure( result, editorStructure );
		} catch ( error ) {
			const errorMessage = sprintf(
				/* translators: Error message. */
				__(
					'Course modules and lessons could not be updated. %s',
					'sensei-lms'
				),
				error.message
			);
			yield dispatch( 'core/notices' ).createErrorNotice( errorMessage, {
				id: 'course-outline-save-error',
			} );
		}

		yield { type: 'SAVING', isSavingStructure: false };
	},

	/**
	 * Set fetched structure.
	 *
	 * @param {Array} serverStructure
	 * @param {Array} editorStructure
	 */
	*setStructure( serverStructure, editorStructure = null ) {
		yield actions.setServerStructure( serverStructure, editorStructure );
		yield actions.updateOutlineBlock( serverStructure );
	},

	/**
	 * Keep last fetched server state for comparison.
	 *
	 * @param {Array} serverStructure
	 * @param {Array} editorStructure
	 */
	setServerStructure: ( serverStructure, editorStructure = null ) => ( {
		type: 'SET_SERVER_STRUCTURE',
		serverStructure,
		hasStructureUpdate:
			editorStructure && ! isEqual( serverStructure, editorStructure ),
	} ),

	/**
	 * Update outline block.
	 *
	 * @param {Array} structure
	 */
	*updateOutlineBlock( structure ) {
		const { clientId = null } = getEditorOutlineBlock();

		if ( ! clientId || ! structure || 0 === structure.length ) {
			return;
		}

		const blocks = yield select( 'core/block-editor' ).getBlocks(
			clientId
		);
		yield dispatch( 'core/block-editor' ).replaceInnerBlocks(
			clientId,
			syncStructureToBlocks( structure, blocks ),
			false
		);
	},

	/**
	 * Clear structure update.
	 */
	clearStructureUpdate: () => ( { type: 'CLEAR_STRUCTURE_UPDATE' } ),
};

/**
 * Course structure reducers.
 */
const reducers = {
	SET_SERVER_STRUCTURE: (
		{ serverStructure, hasStructureUpdate },
		state
	) => {
		return {
			...state,
			serverStructure,
			hasStructureUpdate,
		};
	},
	SAVING: ( { isSavingStructure }, state ) => ( {
		...state,
		isSavingStructure,
	} ),
	CLEAR_STRUCTURE_UPDATE: ( action, state ) => ( {
		...state,
		hasStructureUpdate: false,
	} ),
	DEFAULT: ( action, state ) => state,
};

/**
 * Course structure selectors.
 */
const selectors = {
	shouldResavePost: ( { hasStructureUpdate } ) => hasStructureUpdate,
	getIsSavingStructure: ( { isSavingStructure } ) => isSavingStructure,
	getServerStructure: ( { serverStructure } ) => serverStructure,
};

export const COURSE_STORE = 'sensei/course-structure';

/**
 * Register course structure store and subscribe to block editor save.
 */
const registerCourseStructureStore = () => {
	// Set to true when savings starts, and false when it ends.
	let postSaving = false;

	const startSave = () => {
		const serverStructure = select( COURSE_STORE ).getServerStructure();
		const editorStructure = getEditorOutlineStructure();

		if (
			! editorStructure ||
			isEqual( serverStructure, editorStructure )
		) {
			return;
		}

		// Clear error notices.
		dispatch( 'core/notices' ).removeNotice( 'course-outline-save-error' );
		dispatch( COURSE_STORE ).saveStructure( editorStructure );
	};

	const finishSave = () => {
		const shouldResavePost = select( COURSE_STORE ).shouldResavePost();

		if ( ! shouldResavePost ) {
			return;
		}

		dispatch( 'core/editor' ).savePost();
		dispatch( COURSE_STORE ).clearStructureUpdate();
	};

	subscribe( function saveStructureOnPostSave() {
		const editor = select( 'core/editor' );

		if ( ! editor ) return;

		const isSavingPost =
			editor.isSavingPost() && ! editor.isAutosavingPost();
		const isSavingStructure = select( COURSE_STORE ).getIsSavingStructure();

		if ( ! postSaving && isSavingPost ) {
			// First update where post is saving.
			postSaving = true;
			startSave();
		} else if ( postSaving && ! isSavingPost && ! isSavingStructure ) {
			// First update where post is no longer saving and editor is sync.
			postSaving = false;
			finishSave();
		}
	} );

	registerStore( COURSE_STORE, {
		reducer: createReducerFromActionMap( reducers, DEFAULT_STATE ),
		actions,
		selectors,
		controls: { ...dataControls },
	} );
};

registerCourseStructureStore();
