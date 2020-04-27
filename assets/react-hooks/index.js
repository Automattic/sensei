import { useLayoutEffect } from '@wordpress/element';

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
