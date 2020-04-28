import { useQueryStringRouter } from '../query-string-router';

const Route = ( { route, defaultRoute, children } ) => {
	const { currentRoute, updateRoute } = useQueryStringRouter();

	if ( currentRoute === route ) {
		return children;
	}

	// If this is the default route and the route is note defined, set this route.
	if ( ! currentRoute && defaultRoute ) {
		updateRoute( route, true );
	}

	return null;
};

export default Route;
