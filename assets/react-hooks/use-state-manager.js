import { useState, useMemo } from '@wordpress/element';

/**
 * Use a state manager function that exposes actions and updates the state.
 *
 * @param {Function} Manager
 * @return {[Object, Function[]]} State and actions.
 */
export function useStateManager( Manager ) {
	const [ state, setState ] = useState( null );
	const actions = useMemo( () => Manager( setState ), [ Manager, setState ] );

	return [ state, actions ];
}
