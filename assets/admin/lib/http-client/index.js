/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

const parseRestRoute = ( options, next ) => {
	const restRoute = options.restRoute
		? {
				path: addQueryArgs( '/', { rest_route: options.restRoute } ),
		  }
		: null;
	return next( { ...options, ...restRoute } );
};

apiFetch.use( parseRestRoute );

export default apiFetch;
