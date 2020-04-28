import { useQueryStringRouter } from '../query-string-router';

const Route = ( { route, defaultRoute, children } ) => {
	const { currentRoute } = useQueryStringRouter();

	if ( currentRoute === route || ( ! currentRoute && defaultRoute ) ) {
		return children;
	}

	return null;
};

export default Route;
