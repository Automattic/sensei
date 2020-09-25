import { apiFetch, controls as dataControls } from '@wordpress/data-controls';
import { dispatch, registerStore, select, subscribe } from '@wordpress/data';
import { createReducerFromActionMap } from '../../shared/data/store-helpers';
import { isEqual } from 'lodash';

const DEFAULT_STATE = {
	structure: [],
	editor: [],
	isSaving: false,
	isEditorDirty: false,
	hasChanges: false,
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

		yield { type: 'SAVING', isSaving: true };
		const courseId = yield select( 'core/editor' ).getCurrentPostId();
		try {
			const result = yield apiFetch( {
				path: `/sensei-internal/v1/course-structure/${ courseId }`,
				method: 'POST',
				data: { structure: yield getEditorStructure() },
			} );
			yield actions.setStructure( result );
		} catch ( error ) {
			yield dispatch( 'core/notices' ).createErrorNotice( error.message );
		}

		yield { type: 'SAVING', isSaving: false };
	},
	setStructure: ( structure ) => ( { type: 'SET_SERVER', structure } ),
	setEditorStructure: ( structure ) => {
		return { type: 'SET_EDITOR', structure };
	},
	setEditorDirty: ( isEditorDirty ) => {
		return { type: 'SET_DIRTY', isEditorDirty };
	},
};

/**
 * Course structure reducers.
 */
const reducers = {
	SET_SERVER: ( { structure }, state ) => {
		const hasStructureUpdate =
			state.structure.length && ! isEqual( structure, state.editor );
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
	SAVING: ( { isSaving }, state ) => ( {
		...state,
		isSaving,
	} ),
	SET_DIRTY: ( { isEditorDirty }, state ) => ( {
		...state,
		isEditorDirty,
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
	shouldSave: ( { isEditorDirty, isSaving } ) => ! isSaving && isEditorDirty,
	shouldResavePost: ( { isEditorDirty, isSaving, hasStructureUpdate } ) =>
		! isSaving && isEditorDirty && hasStructureUpdate,
};

export const COURSE_STORE = 'sensei/course-structure';

/**
 * Register course structure store and subscribe to block editor save.
 */
const registerCourseStructureStore = () => {
	subscribe( function saveStructureOnPostSave() {
		const editor = select( 'core/editor' );

		if ( ! editor ) return;

		const isSaving = editor.isSavingPost() && ! editor.isAutosavingPost();
		const shouldSave = select( COURSE_STORE ).shouldSave();
		const shouldResavePost = select( COURSE_STORE ).shouldResavePost();

		if ( isSaving && shouldSave && ! shouldResavePost ) {
			dispatch( COURSE_STORE ).save();
		}

		// Save the post again if the blocks were updated.
		if ( ! isSaving && shouldResavePost ) {
			dispatch( 'core/editor' ).savePost();
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
