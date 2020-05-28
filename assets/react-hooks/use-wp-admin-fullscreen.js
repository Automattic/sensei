import { useLayoutEffect } from '@wordpress/element';

/**
 * Apply fullscreen view by hiding wp-admin elements via CSS.
 *
 * Allows setting additional classes on the body element.
 * Fullscreen and classes are added when the component is mounted, and removed when unmounted.
 *
 * @param {string[]} bodyClasses Additional classes to be set.
 * @param {boolean} [active]     Enable or disable fullscreen.
 */
const useWpAdminFullscreen = ( bodyClasses = [], active = true ) => {
	const classes = [ ...bodyClasses, 'sensei-wp-admin-fullscreen' ];

	const setupGlobalStyles = () => {
		toggleGlobalStyles( active );
		return toggleGlobalStyles.bind( null, ! active );
	};

	const toggleGlobalStyles = ( enabled ) => {
		if ( enabled ) document.body.classList.add( ...classes );
		else document.body.classList.remove( ...classes );

		document.documentElement.classList.toggle( 'wp-toolbar', ! enabled );
	};

	useLayoutEffect( setupGlobalStyles, [ bodyClasses ] );
};

export default useWpAdminFullscreen;
