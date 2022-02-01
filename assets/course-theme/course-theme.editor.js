/* eslint-disable @wordpress/no-global-event-listener */
/**
 * WordPress dependencies
 */
import { getQueryArgs, addQueryArgs } from '@wordpress/url';

window.addEventListener( 'locationchange', redirectToCourseThemeOverride );
window.addEventListener( 'popstate', redirectToCourseThemeOverride );

/**
 * Reload the page when opening or closing a course theme template, to ensure the active theme styles are not loaded.
 */
function redirectToCourseThemeOverride() {
	const query = getQueryArgs( document.location );
	const isCourseThemeDocument =
		query.postId && query.postId.match( /sensei-course-theme/ );

	const isCourseThemeActive = query.learn;

	query.learn = isCourseThemeDocument ? '1' : undefined;
	if ( !! query.learn !== !! isCourseThemeActive ) {
		const url = addQueryArgs( document.location.path, query );
		document.body.style.display = 'none';
		document.location.replace( url );
	}
}

/**
 * Monkey-patch history.pushState and replaceState to provide events for location change.
 */

const { replaceState, pushState } = window.history;

window.history.replaceState = ( ...args ) => {
	replaceState.apply( window.history, args );
	window.dispatchEvent( new window.Event( 'locationchange', args ) );
};

window.history.pushState = ( ...args ) => {
	pushState.apply( window.history, args );
	window.dispatchEvent( new window.Event( 'locationchange', args ) );
};
