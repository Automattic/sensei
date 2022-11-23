/**
 * WordPress dependencies
 */
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';

/**
 * Use data preloaded with createPreloadingMiddleware only once.
 */
export function preloadedDataUsedOnceMiddleware() {
	const usedPreload = {};

	return ( request, next ) => {
		if (
			'string' === typeof request.path &&
			( 'GET' === request.method || ! request.method )
		) {
			if ( usedPreload[ request.path ] ) {
				request.path = addQueryArgs( request.path, {
					__skip_preload: 1,
				} );
			} else {
				usedPreload[ request.path ] = true;
			}
		}

		return next( request );
	};
}

apiFetch.use( preloadedDataUsedOnceMiddleware() );
