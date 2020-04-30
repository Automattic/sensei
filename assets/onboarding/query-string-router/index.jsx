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
 * It creates a provider with the following values in the context:
 * - `currentRoute`: The string of the current route.
 * - `goTo`: Functions that send the user to another route.
 *
 * @param {Object} props
 * @param {string} props.paramName Param used as reference in the query string.
 * @param {Object} props.children  Render this children if it matches the route.
 */
const QueryStringRouter = ( { paramName, children } ) => {
	// Current route.
	const [ currentRoute, setRoute ] = useState(
		getCurrentRouteFromURL( paramName )
	);

	// Provider value.
	const providerValue = useMemo( () => {
		/**
		 * Functions that send the user to another route.
		 * It changes the URL and update the state of the current route.
		 *
		 * @param {string}  newRoute New route to send the user.
		 * @param {boolean} replace  Flag to mark if should replace or push state.
		 */
		const goTo = ( newRoute, replace = false ) => {
			updateRouteURL( paramName, newRoute, replace );
			setRoute( newRoute );
		};

		return {
			currentRoute,
			goTo,
		};
	}, [ currentRoute, paramName ] );

	// Handle history changes through popstate.
	useEventListener(
		'popstate',
		() => {
			setRoute( getCurrentRouteFromURL( paramName ) );
		},
		[ paramName ]
	);

	return (
		<QueryStringRouterContext.Provider value={ providerValue }>
			{ children }
		</QueryStringRouterContext.Provider>
	);
};

export default QueryStringRouter;

/**
 * Export `Route` component as part of the query string router.
 */
export { default as Route } from './route';

/**
 * Hook to access the query string router values from the context.
 *
 * @return {QueryStringRouterContext} Query string router context.
 *
 * @typedef  {Object}           QueryStringRouterContext
 * @property {string}           currentRoute             Current route.
 * @property {function(string)} goTo                     Function to navigate between routes.
 */
export const useQueryStringRouter = () =>
	useContext( QueryStringRouterContext );
