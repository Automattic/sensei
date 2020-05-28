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
	const classes = [ ...bodyClasses, 'sensei-wp-admin-fullscreen--active' ];

	const setupGlobalStyles = () => {
		document.body.classList.add( 'sensei-wp-admin-fullscreen' );
		toggleGlobalStyles( active );
		return toggleGlobalStyles.bind( null, ! active );
	};

	const toggleGlobalStyles = ( enabled ) => {
		if ( enabled ) document.body.classList.add( ...classes );
		else document.body.classList.remove( ...classes );
	};

	useLayoutEffect( setupGlobalStyles, [ bodyClasses ] );
};

export default useWpAdminFullscreen;
