import { apiFetch, controls as dataControls } from '@wordpress/data-controls';
import { dispatch, registerStore, select, subscribe } from '@wordpress/data';
import { createReducerFromActionMap } from '../../shared/data/store-helpers';

const DEFAULT_STATE = {
	structure: [],
};

const actions = {
	/**
	 * Fetch course structure data from REST API.
	 */
	*fetchCourseStructure() {
		const courseId = yield select( 'core/editor' ).getCurrentPostId();
		const result = yield apiFetch( {
			path: `/sensei-internal/v1/course-structure/${ courseId }`,
		} );
		yield actions.setStructure( result );
	},
	/**
	 * Persist editor's course structure to the REST API
	 */
	*save() {
		const shouldSave = yield select( COURSE_STORE ).shouldSave();
		if ( ! shouldSave ) return;

		yield { type: 'SAVING', isSaving: true };
		const courseId = yield select( 'core/editor' ).getCurrentPostId();
		try {
			yield apiFetch( {
				path: `/sensei-internal/v1/course-structure/${ courseId }`,
				method: 'POST',
				data: yield select( COURSE_STORE ).getEditorStructure(),
			} );
		} catch {
			yield { type: 'SAVING', isSaving: false };
		}

		yield* actions.fetchCourseStructure();
	},
	setStructure: ( structure ) => ( { type: 'SET_SERVER', structure } ),
	setEditorStructure: ( structure ) => ( { type: 'SET_EDITOR', structure } ),
};

/**
 * Course structure reducers.
 */
const reducers = {
	SET_SERVER: ( { structure }, state ) => ( {
		...state,
		structure,
		editor: structure,
		isDirty: false,
		isSaving: false,
	} ),
	SET_EDITOR: ( { structure }, state ) => ( {
		...state,
		editor: structure,
		isDirty: true,
		isSaving: false,
	} ),
	SAVING: ( { isSaving }, state ) => ( { ...state, isSaving } ),
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
	shouldSave: ( { isDirty, isSaving } ) => isDirty && ! isSaving,
};

export const COURSE_STORE = 'sensei/course-structure';

/**
 * Register course structure store and subscribe to block editor save.
 */
const registerCourseStructureStore = () => {
	subscribe( () => {
		const { isSavingPost, isAutosavingPost } = select( 'core/editor' );

		if ( isSavingPost() && ! isAutosavingPost() ) {
			dispatch( COURSE_STORE ).save();
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
