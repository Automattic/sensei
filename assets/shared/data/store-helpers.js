/**
 * WordPress dependencies
 */
import { createReduxStore, register, registerStore } from '@wordpress/data';

/**
 * Compose an action creator with the given start, success and error actions.
 *
 * @param {string}   startAction   Start action type.
 * @param {Function} fetchFn       The action creator to be wrapped. Should return the resolved data.
 * @param {string}   successAction Success action type.
 * @param {string}   errorAction   Error action type.
 * @return {Function} The wrapped action creator.
 */
export const composeFetchAction = (
	startAction,
	fetchFn,
	successAction,
	errorAction
) =>
	function* ( ...args ) {
		if ( startAction ) {
			yield { type: startAction };
		}

		try {
			const data = yield* fetchFn( ...args );
			yield { type: successAction, data };
		} catch ( error ) {
			yield { type: errorAction, error };
		}
	};

/**
 * Create reducer from a map of action type keys and reducer function.
 *
 * @example
 *  createSimpleReducer({ SAMPLE_ACTION: ( { actionProperty }, state ) => ({ ...state, actionProperty }) )
 *
 * @param {Object} reducers     Map of action type - reducer functions.
 * @param {Object} defaultState Default state.
 *
 * @return {Function} Store reducer.
 */
export const createReducerFromActionMap = ( reducers, defaultState ) => {
	return ( state = defaultState, action ) => {
		const reducer = reducers[ action.type ] || reducers.DEFAULT;
		return reducer( action, state );
	};
};

/**
 * Create and register a WP data store.
 *
 * Uses the available newest @wordpress/data API to register the store and provide the constant to use it.
 *
 * @param {string} name     Store name.
 * @param {Object} settings Store definition.
 * @return {string|Object} Store key.
 */
export const createStore = ( name, settings ) => {
	if ( createReduxStore ) {
		const store = createReduxStore( name, settings );
		register( store );
		return store;
	}
	registerStore( name, settings );
	return name;
};
