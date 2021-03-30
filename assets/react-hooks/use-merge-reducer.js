/**
 * WordPress dependencies
 */
import { useReducer } from '@wordpress/element';

/**
 * Shallow-merge new value into state object.
 *
 * @param {Object} initialState Initial state.
 * @return {Array.<(Object|Function)>} State object and updateState function.
 */
export const useMergeReducer = ( initialState ) => {
	return useReducer(
		( state, diff ) => ( { ...state, ...diff } ),
		initialState
	);
};
