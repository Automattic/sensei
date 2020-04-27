import { useEffect, useLayoutEffect, useCallback } from '@wordpress/element';

/**
 * Hook for event listeners.
 *
 * @param {string}   eventName Event name to be attached.
 * @param {Function} handler   Handler function to the event.
 * @param {Array}    deps      Dependencies of the handler function.
 * @param {Object}   element   Element to attach the event. Default is `window`.
 */
export const useEventListener = (
	eventName,
	handler,
	deps,
	element = window
) => {
	const handlerCallback = useCallback( handler, deps );

	useEffect( () => {
		const evtArgs = [ eventName, handlerCallback, false ];
		element.addEventListener( ...evtArgs );

		return () => {
			element.removeEventListener( ...evtArgs );
		};
	}, [ eventName, handlerCallback, element ] );
};

/**
 * Apply fullscreen view by hiding wp-admin elements via CSS.
 *
 * Allows setting additional classes on the body element.
 * Fullscreen and classes are added when the component is mounted, and removed when unmounted.
 *
 * @param {string[]} bodyClasses Additional classes to be set.
 */
export const useFullScreen = ( bodyClasses = [] ) => {
	const classes = [ ...bodyClasses, 'sensei-wp-admin-fullscreen' ];

	const setupGlobalStyles = () => {
		toggleGlobalStyles( true );
		return toggleGlobalStyles.bind( null, false );
	};

	const toggleGlobalStyles = ( enabled ) => {
		if ( enabled ) document.body.classList.add( ...classes );
		else document.body.classList.remove( ...classes );

		document.documentElement.classList.toggle( 'wp-toolbar', ! enabled );
	};

	useLayoutEffect( setupGlobalStyles, [ bodyClasses ] );
};
