/**
 * WordPress dependencies
 */
import { useCallback, useEffect, useRef } from '@wordpress/element';

// Solution borrowed from https://gist.github.com/kentcdodds/b36572b6e9227207e6c71fd80e63f3b4.
export default function useAbortController() {
	const abortControllerRef = useRef();

	const getAbortController = useCallback( () => {
		if ( ! abortControllerRef.current ) {
			abortControllerRef.current = new AbortController();
		}

		return abortControllerRef.current;
	}, [] );

	useEffect( () => {
		return () => getAbortController().abort();
	}, [ getAbortController ] );

	const getSignal = useCallback( () => getAbortController().signal, [
		getAbortController,
	] );

	return { getSignal };
}
