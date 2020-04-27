import {
	useState,
	useMemo,
	useContext,
	createContext,
} from '@wordpress/element';

import { useEventListener } from '../../react-hooks';
import { updateRouteURL, getCurrentRouteFromURL } from './url-functions';

/**
 * Query string router context.
 */
const QueryStringRouterContext = createContext();

/**
 * Query string router component.
 */
export const QueryStringRouter = ( { routes, queryStringName, children } ) => {
	// Current route.
	const [ currentRoute, setRoute ] = useState(
		getCurrentRouteFromURL( queryStringName, routes )
	);

	// Provider value.
	const providerValue = useMemo( () => {
		const { container } = routes.find( ( r ) => currentRoute === r.key );
		const updateRoute = ( newRoute ) => {
			updateRouteURL( queryStringName, newRoute );
			setRoute( newRoute );
		};

		return {
			currentRoute,
			currentContainer: container,
			updateRoute,
		};
	}, [ currentRoute, routes, queryStringName, setRoute ] );

	// Handle history changes through popstate.
	useEventListener(
		'popstate',
		() => {
			setRoute( getCurrentRouteFromURL( queryStringName, routes ) );
		},
		[ setRoute, routes, queryStringName ]
	);

	return (
		<QueryStringRouterContext.Provider value={ providerValue }>
			{ children }
		</QueryStringRouterContext.Provider>
	);
};

/**
 * Hook to access the query string router value.
 */
export const useQueryStringRouter = () =>
	useContext( QueryStringRouterContext );
