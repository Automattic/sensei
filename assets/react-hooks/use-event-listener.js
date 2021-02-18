/**
 * WordPress dependencies
 */
import { useEffect, useCallback } from '@wordpress/element';

/**
 * Hook for event listeners.
 *
 * @param {string}   eventName Event name to be attached.
 * @param {Function} handler   Handler function to the event.
 * @param {Array}    deps      Dependencies of the handler function.
 * @param {Object}   element   Element to attach the event. Default is `window`.
 */
const useEventListener = ( eventName, handler, deps, element = window ) => {
	// eslint-disable-next-line react-hooks/exhaustive-deps
	const handlerCallback = useCallback( handler, deps );

	useEffect( () => {
		const evtArgs = [ eventName, handlerCallback, false ];
		element.addEventListener( ...evtArgs );

		return () => {
			element.removeEventListener( ...evtArgs );
		};
	}, [ eventName, handlerCallback, element ] );
};

export default useEventListener;
