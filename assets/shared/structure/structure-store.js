/**
 * External dependencies
 */
import { isEqual } from 'lodash';

/**
 * WordPress dependencies
 */
import { apiFetch, controls as dataControls } from '@wordpress/data-controls';
import { dispatch, registerStore, select, subscribe } from '@wordpress/data';
/**
 * Internal dependencies
 */
import { createReducerFromActionMap } from '../data/store-helpers';
import '../data/api-fetch-preloaded-once';

/**
 * Register structure store and subscribe to block editor save.
 *
 * @param {Object}   opts
 * @param {string}   opts.storeName          Name of store.
 * @param {Function} opts.getEndpoint        REST API endpoint.
 * @param {Function} opts.saveError          Handler for displaying save errors.
 * @param {Function} opts.fetchError         Handler for displaying fetch errors.
 * @param {Function} opts.clearError         Handler for clearing errors.
 * @param {Function} opts.updateBlock        Update block with given structure.
 * @param {Function} opts.readBlock          Extract structure from block.
 * @param {Function} opts.setServerStructure Set the server structure which is used to track differences.
 */
export function registerStructureStore( {
	storeName,
	getEndpoint,
	saveError,
	fetchError,
	clearError,
	updateBlock,
	readBlock,
	setServerStructure,
	...store
} ) {
	const DEFAULT_STATE = {
		serverStructure: null,
		editorStructure: null,
		isSavingStructure: false,
		hasUnsavedServerUpdates: false,
		hasUnsavedEditorChanges: false,
		clientId: null,
	};

	const actions = {
		/**
		 * Fetch structure data from REST API.
		 */
		*loadStructure() {
			try {
				const endpoint = yield* getEndpoint();
				const result = yield apiFetch( {
					path: `/sensei-internal/v1/${ endpoint }`,
					method: 'GET',
				} );
				yield actions.setResult( result );
			} catch ( error ) {
				yield fetchError?.( error );
			}
		},

		/**
		 * Persist editor's structure to the REST API.
		 */
		*saveStructure() {
			const editorStructure = yield select(
				storeName
			).getEditorStructure();
			try {
				const endpoint = yield* getEndpoint();
				const result = yield apiFetch( {
					path: `/sensei-internal/v1/${ endpoint }`,
					method: 'POST',
					data: editorStructure,
				} );
				yield actions.setResult( result );
			} catch ( error ) {
				yield saveError?.( error );
			}
		},

		/**
		 * Set fetched structure.
		 *
		 * @param {Array} serverStructure
		 */
		*setResult( serverStructure ) {
			yield actions.setServerStructure( serverStructure );
			yield updateBlock( serverStructure );
		},

		/**
		 * Keep last fetched server state for comparison.
		 *
		 * @param {Array} serverStructure
		 */
		setServerStructure: ( serverStructure ) => {
			return {
				type: 'SET_SERVER_STRUCTURE',
				serverStructure,
			};
		},

		/**
		 * Keep last editor state.
		 *
		 * @param {Array} editorStructure
		 */
		setEditorStructure: ( editorStructure ) => {
			return {
				type: 'SET_EDITOR_STRUCTURE',
				editorStructure,
			};
		},

		/**
		 * Initiate saving the post.
		 */
		*savePost() {
			yield { type: 'SAVE_POST' };
			yield dispatch( 'core/editor' ).savePost();
		},

		/**
		 * Post is saving. Save the structure too if it has changed.
		 */
		*startPostSave() {
			yield { type: 'START_SAVE' };
			const editorStructure = readBlock();
			yield actions.setEditorStructure( editorStructure );

			yield clearError?.();
			if ( ! editorStructure ) return;

			if ( select( storeName ).hasUnsavedEditorChanges() ) {
				yield* actions.saveStructure();
			}

			yield { type: 'FINISH_SAVE' };
		},

		/**
		 * Finished saving post and structure.
		 * Check if either needs to be saved again due to new changes.
		 */
		*finishPostSave() {
			yield { type: 'FINISH_POST_SAVE' };
			const { hasUnsavedServerUpdates } = select( storeName );

			if ( hasUnsavedServerUpdates() ) {
				yield* actions.savePost();
			}
		},

		/**
		 * Set linked block.
		 *
		 * @param {string} clientId Block ID.
		 */
		*setBlock( clientId ) {
			yield { type: 'SET_BLOCK', clientId };
		},
	};

	/**
	 * Structure store reducers.
	 */
	const reducers = {
		SET_SERVER_STRUCTURE: ( { serverStructure }, state ) => {
			const initialChange = ! state.editorStructure;
			const newStructure = setServerStructure
				? setServerStructure( serverStructure )
				: serverStructure;
			const hasDiff =
				! initialChange &&
				! isEqual( newStructure, state.editorStructure );

			return {
				...state,
				serverStructure: newStructure,
				hasUnsavedServerUpdates: hasDiff,
				hasUnsavedEditorChanges: false,
			};
		},
		SET_EDITOR_STRUCTURE: ( { editorStructure }, state ) => {
			const hasDiff = ! isEqual( state.serverStructure, editorStructure );
			return {
				...state,
				editorStructure,
				hasUnsavedEditorChanges: hasDiff && !! editorStructure,
			};
		},
		START_SAVE: ( action, state ) => ( {
			...state,
			isSavingStructure: true,
		} ),
		FINISH_SAVE: ( action, state ) => ( {
			...state,
			isSavingStructure: false,
		} ),
		SAVE_POST: ( action, state ) => ( {
			...state,
			hasUnsavedServerUpdates: false,
		} ),
		SET_BLOCK: ( { clientId }, state ) => ( { ...state, clientId } ),
		DEFAULT: ( action, state ) => state,
	};

	/**
	 * Store state selectors.
	 */
	const selectors = {
		hasUnsavedServerUpdates: ( { hasUnsavedServerUpdates } ) =>
			hasUnsavedServerUpdates,
		hasUnsavedEditorChanges: ( { hasUnsavedEditorChanges } ) =>
			hasUnsavedEditorChanges,
		getIsSavingStructure: ( { isSavingStructure } ) => isSavingStructure,
		getServerStructure: ( { serverStructure } ) => serverStructure,
		getEditorStructure: ( { editorStructure } ) => editorStructure,
		getBlock: ( { clientId } ) => clientId,
	};

	const subscribeToPostSave = () => {
		let structureStartedSaving = false;
		let editorStartedSaving = false;

		return subscribe( function saveStructureOnPostSave() {
			const editor = select( 'core/editor' );

			if ( ! editor ) {
				return;
			}

			const isSavingPost =
				editor.isSavingPost() && ! editor.isAutosavingPost();
			const isSavingStructure = select(
				storeName
			).getIsSavingStructure();

			if ( isSavingPost ) {
				editorStartedSaving = true;
			}

			if (
				! structureStartedSaving &&
				! isSavingPost &&
				editorStartedSaving
			) {
				// Start saving structure when post has finished saving.
				structureStartedSaving = true;
				editorStartedSaving = false;
				dispatch( storeName ).startPostSave();
			} else if ( structureStartedSaving && ! isSavingStructure ) {
				// Call finishPostSave when structure has finished saving.
				structureStartedSaving = false;
				dispatch( storeName ).finishPostSave();
			}
		} );
	};

	return {
		unsubscribe: subscribeToPostSave(),
		store: registerStore( storeName, {
			reducer: createReducerFromActionMap(
				{ ...reducers, ...store?.reducers },
				DEFAULT_STATE
			),
			actions: { ...actions, ...store?.actions },
			selectors: { ...selectors, ...store?.selectors },
			controls: { ...dataControls, ...store?.controls },
		} ),
	};
}
