import { useQueryStringRouter } from './index';

/**
 * Route component. It works inner the `QueryStringRouter context.
 *
 * @param {Object}  props
 * @param {string}  props.route        Route name.
 * @param {boolean} props.defaultRoute Flag if it is the default route.
 * @param {Object}  props.children     Render this children if it matches the route.
 *
 * @return {Object|null} Return the children if the routes match. Otherwise return null.
 */
const Route = ( { route, defaultRoute, children } ) => {
	const { currentRoute, goTo } = useQueryStringRouter();

	if ( currentRoute === route ) {
		return children;
	}

	// If this is the default route and the route is note defined, set this route.
	if ( ! currentRoute && defaultRoute ) {
		goTo( route, true );
	}

	return null;
};

export default Route;
