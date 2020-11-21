import { apiFetch, controls as dataControls } from '@wordpress/data-controls';
import { dispatch, registerStore, select, subscribe } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { createReducerFromActionMap } from '../../shared/data/store-helpers';
import { isEqual } from 'lodash';

const DEFAULT_STATE = {
	structure: null,
	editor: [],
	isSavingStructure: false,
	isEditorDirty: false,
	hasStructureUpdate: false,
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
	 * Persist editor's course structure to the REST API
	 */
	*save() {
		const { getEditorStructure } = select( COURSE_STORE );

		yield { type: 'SAVING', isSavingStructure: true };
		const courseId = yield select( 'core/editor' ).getCurrentPostId();
		try {
			const result = yield apiFetch( {
				path: `/sensei-internal/v1/course-structure/${ courseId }`,
				method: 'POST',
				data: { structure: yield getEditorStructure() },
			} );
			yield actions.setStructure( result );
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
	setStructure: ( structure ) => ( { type: 'SET_SERVER', structure } ),
	setEditorStructure: ( structure ) => ( { type: 'SET_EDITOR', structure } ),
	setEditorDirty: ( isEditorDirty ) => ( {
		type: 'SET_DIRTY',
		isEditorDirty,
	} ),
	clearStructureUpdate: () => ( { type: 'CLEAR_STRUCTURE_UPDATE' } ),
};

/**
 * Course structure reducers.
 */
const reducers = {
	SET_SERVER: ( { structure }, state ) => {
		const hasStructureUpdate =
			state.structure && ! isEqual( structure, state.editor );
		return {
			...state,
			structure,
			editor: structure,
			isEditorDirty: false,
			hasStructureUpdate,
		};
	},
	SET_EDITOR: ( { structure }, state ) => {
		const isEditorDirty = ! isEqual( structure, state.structure );
		return {
			...state,
			editor: structure,
			isEditorDirty,
			hasStructureUpdate: state.hasStructureUpdate && isEditorDirty,
		};
	},
	SAVING: ( { isSavingStructure }, state ) => ( {
		...state,
		isSavingStructure,
	} ),
	SET_DIRTY: ( { isEditorDirty }, state ) => ( {
		...state,
		isEditorDirty,
	} ),
	CLEAR_STRUCTURE_UPDATE: ( state ) => ( {
		...state,
		hasStructureUpdate: false,
	} ),
	DEFAULT: ( action, state ) => state,
};

/**
 * Course structure resolvers.
 */
const resolvers = {
	getStructure: () => actions.fetchCourseStructure(),
};

/**
 * Course structure  selectors
 */
const selectors = {
	getStructure: ( { structure } ) => structure,
	getEditorStructure: ( { editor } ) => editor,
	shouldSave: ( { isEditorDirty, isSavingStructure } ) =>
		! isSavingStructure && isEditorDirty,
	getIsSavingStructure: ( { isSavingStructure } ) => isSavingStructure,
	shouldResavePost: ( { isSavingStructure, hasStructureUpdate } ) =>
		! isSavingStructure && hasStructureUpdate,
};

export const COURSE_STORE = 'sensei/course-structure';

/**
 * Register course structure store and subscribe to block editor save.
 */
const registerCourseStructureStore = () => {
	// Set to true when savings starts, and false when it ends.
	let postSaving = false;

	const startSave = () => {
		const shouldSave = select( COURSE_STORE ).shouldSave();

		// Clear error notices.
		dispatch( 'core/notices' ).removeNotice( 'course-outline-save-error' );

		if ( shouldSave ) {
			dispatch( COURSE_STORE ).save();
		}
	};

	const finishSave = () => {
		// Save the post again if the blocks were updated.
		const shouldResavePost = select( COURSE_STORE ).shouldResavePost();
		if ( shouldResavePost ) {
			dispatch( 'core/editor' ).savePost();
			dispatch( COURSE_STORE ).clearStructureUpdate();
		}
	};

	subscribe( function saveStructureOnPostSave() {
		const editor = select( 'core/editor' );

		if ( ! editor ) return;

		const isSavingPost =
			editor.isSavingPost() && ! editor.isAutosavingPost();
		const isSavingStructure = select( COURSE_STORE ).getIsSavingStructure();

		// First update where post is saving.
		if ( ! postSaving && isSavingPost ) {
			postSaving = true;
			startSave();

			// First update where post is no longer saving.
		} else if ( postSaving && ! isSavingPost && ! isSavingStructure ) {
			postSaving = false;
			finishSave();
		}
	} );

	registerStore( COURSE_STORE, {
		reducer: createReducerFromActionMap( reducers, DEFAULT_STATE ),
		actions,
		selectors,
		resolvers,
		controls: { ...dataControls },
	} );
};

registerCourseStructureStore();
