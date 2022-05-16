/**
 * External dependencies
 */
import { debounce } from 'lodash';

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { useState, useEffect, useCallback } from '@wordpress/element';

/**
 * Use select hook with debounce.
 *
 * @param {Function} mapSelect Map select function.
 * @param {Array}    deps      Use select dependencies.
 * @param {number}   wait      Wait time for the debounce.
 *
 * @return {*} Returns what useSelect returns through the mapSelect argument.
 */
const useSelectWithDebounce = ( mapSelect, deps, wait ) => {
	const [ depsState, setDepsState ] = useState( deps );

	// eslint-disable-next-line react-hooks/exhaustive-deps -- Using debounce as callback.
	const debounceSetDepsState = useCallback( debounce( setDepsState, wait ), [
		setDepsState,
		wait,
	] );

	useEffect( () => {
		debounceSetDepsState( deps );
		// eslint-disable-next-line react-hooks/exhaustive-deps -- Dependencies coming from args.
	}, deps );

	return useSelect( mapSelect, depsState );
};

export default useSelectWithDebounce;
