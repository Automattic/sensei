/**
 * Internal dependencies
 */
import { useQueryStringRouter } from './index';

/**
 * Route component. It works inner the `QueryStringRouter context.
 *
 * @param {Object} props
 * @param {string} props.route    Route name.
 * @param {Object} props.children Render this children if it matches the route.
 *
 * @return {Object|null} Return the children if the routes match. Otherwise return null.
 */
const Route = ( { route, children } ) => {
	const { currentRoute } = useQueryStringRouter();

	if ( currentRoute === route ) {
		return children;
	}

	return null;
};

export default Route;
