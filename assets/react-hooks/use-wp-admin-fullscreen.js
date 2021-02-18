/**
 * WordPress dependencies
 */
import { useLayoutEffect } from '@wordpress/element';

const toggleGlobalStyles = ( enabled, classes ) => {
	if ( enabled ) {
		document.body.classList.add( ...classes );
	} else {
		document.body.classList.remove( ...classes );
	}

	document.documentElement.classList.toggle( 'wp-toolbar', ! enabled );
};

/**
 * Apply fullscreen view by hiding wp-admin elements via CSS.
 *
 * Allows setting additional classes on the body element.
 * Fullscreen and classes are added when the component is mounted, and removed when unmounted.
 *
 * @param {string[]} bodyClasses Additional classes to be set.
 */
const useWpAdminFullscreen = ( bodyClasses = [] ) => {
	useLayoutEffect( () => {
		const classes = [ ...bodyClasses, 'sensei-wp-admin-fullscreen' ];

		toggleGlobalStyles( true, classes );

		return () => {
			toggleGlobalStyles( false, classes );
		};
	}, [ bodyClasses ] );
};

export default useWpAdminFullscreen;
