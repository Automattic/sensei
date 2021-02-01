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

/**
 * Register structure store and subscribe to block editor save.
 *
 * @param {Object}   opts
 * @param {string}   opts.storeName   Name of store.
 * @param {Function} opts.getEndpoint REST API endpoint.
 * @param {Function} opts.saveError   Handler for displaying errors.
 * @param {Function} opts.clearError  Handler for clearing errors.
 * @param {Function} opts.updateBlock Update block with given structure.
 * @param {Function} opts.readBlock   Extract structure from block.
 */
export function registerStructureStore( {
	storeName,
	getEndpoint,
	saveError,
	clearError,
	updateBlock,
	readBlock,
} ) {
	const DEFAULT_STATE = {
		serverStructure: null,
		isSavingStructure: false,
		hasStructureUpdate: false,
	};

	const actions = {
		/**
		 * Fetch course structure data from REST API.
		 */
		*loadStructure() {
			const endpoint = yield* getEndpoint();
			const result = yield apiFetch( {
				path: `/sensei-internal/v1/${ endpoint }?context=edit`,
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

			try {
				const endpoint = yield* getEndpoint();
				const result = yield apiFetch( {
					path: `/sensei-internal/v1/${ endpoint }`,
					method: 'POST',
					data: { structure: editorStructure },
				} );
				yield actions.setStructure( result, editorStructure );
			} catch ( error ) {
				yield saveError( error );
			}

			yield { type: 'SAVING', isSavingStructure: false };
		},

		/**
		 * Set fetched structure.
		 *
		 * @param {Array} serverStructure
		 * @param {Array} editorStructure
		 */ *setStructure( serverStructure, editorStructure = null ) {
			yield actions.setServerStructure(
				serverStructure,
				editorStructure
			);
			yield updateBlock( serverStructure );
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
				editorStructure &&
				! isEqual( serverStructure, editorStructure ),
		} ),

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

	const subscribeToPostSave = () => {
		// Set to true when savings starts, and false when it ends.
		let postSaving = false;

		const startSave = () => {
			const serverStructure = select( storeName ).getServerStructure();
			const editorStructure = readBlock();

			if (
				! editorStructure ||
				isEqual( serverStructure, editorStructure )
			) {
				return;
			}

			clearError();
			dispatch( storeName ).saveStructure( editorStructure );
		};

		const finishSave = () => {
			const shouldResavePost = select( storeName ).shouldResavePost();

			if ( ! shouldResavePost ) {
				return;
			}

			dispatch( 'core/editor' ).savePost();
			dispatch( storeName ).clearStructureUpdate();
		};

		subscribe( function saveStructureOnPostSave() {
			const editor = select( 'core/editor' );

			if ( ! editor ) return;

			const isSavingPost =
				editor.isSavingPost() && ! editor.isAutosavingPost();
			const isSavingStructure = select(
				storeName
			).getIsSavingStructure();

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
	};

	subscribeToPostSave();
	registerStore( storeName, {
		reducer: createReducerFromActionMap( reducers, DEFAULT_STATE ),
		actions,
		selectors,
		controls: { ...dataControls },
	} );
}
