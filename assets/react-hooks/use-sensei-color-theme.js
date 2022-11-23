/**
 * WordPress dependencies
 */
import { useLayoutEffect } from '@wordpress/element';

/**
 * Use Sensei color theme.
 *
 * Requires enqueueing the sensei-wp-components stylesheet.
 */
export function useSenseiColorTheme() {
	useLayoutEffect( () => {
		document.body.classList.add( 'sensei-color' );
		return () => document.body.classList.remove( 'sensei-color' );
	} );
}
