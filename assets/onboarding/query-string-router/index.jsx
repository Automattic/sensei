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
export const QueryStringRouter = ( { paramName, children } ) => {
	// Current route.
	const [ currentRoute, setRoute ] = useState(
		getCurrentRouteFromURL( paramName )
	);

	// Provider value.
	const providerValue = useMemo( () => {
		const updateRoute = ( newRoute ) => {
			updateRouteURL( paramName, newRoute );
			setRoute( newRoute );
		};

		return {
			currentRoute,
			updateRoute,
		};
	}, [ currentRoute, paramName, setRoute ] );

	// Handle history changes through popstate.
	useEventListener(
		'popstate',
		() => {
			setRoute( getCurrentRouteFromURL( paramName ) );
		},
		[ setRoute, paramName ]
	);

	return (
		<QueryStringRouterContext.Provider value={ providerValue }>
			{ children }
		</QueryStringRouterContext.Provider>
	);
};

/**
 * Export `Route` component as part of the query string router.
 */
export { default as Route } from './Route';

/**
 * Hook to access the query string router value.
 */
export const useQueryStringRouter = () =>
	useContext( QueryStringRouterContext );
