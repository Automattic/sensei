import { useEffect } from '@wordpress/element';

import './fullscreen.scss';

export function FullScreen( { children } ) {
	function setupGlobalStyles() {
		toggleGlobalStyles( true );
		return toggleGlobalStyles.bind( null, false );
	}

	function toggleGlobalStyles( enabled ) {
		document.body.classList.toggle( 'sensei-wp-admin-fullscreen', enabled );
		document.body.classList.toggle( 'sensei-color', enabled );
		document.documentElement.classList.toggle( 'wp-toolbar', ! enabled );
	}

	useEffect( setupGlobalStyles, [] );

	return <>{ children }</>;
}
