import { useState, useEffect, useMemo } from '@wordpress/element';
import { Stepper } from '@woocommerce/components';
import { get, uniq } from 'lodash';

import { useQueryStringRouter } from '../query-string-router';

/**
 * Merge the navigation state into the routes.
 * Add isComplete and onClick - when visited.
 *
 * @param {Array}    routes        Routes list.
 * @param {string[]} visitedRoutes Key of the visited routes.
 * @param {Function} updateRoute   Function that update the route.
 */
const getRoutesWithNavigationState = ( routes, visitedRoutes, updateRoute ) =>
	routes.map( ( route, index ) => {
		const nextKey = get( routes, [ index + 1, 'key' ], null );

		const routeWithNavigationState = {
			...route,
			isComplete: visitedRoutes.includes( nextKey ),
		};

		if ( visitedRoutes.includes( route.key ) ) {
			routeWithNavigationState.onClick = () => {
				updateRoute( route.key );
			};
		}

		return routeWithNavigationState;
	} );

/**
 * Navigation component.
 */
const Navigation = ( { routes } ) => {
	const { currentRoute, updateRoute } = useQueryStringRouter();

	// Visited routes.
	const [ visitedRoutes, setVisitedRoutes ] = useState( [] );

	useEffect( () => {
		setVisitedRoutes( ( prevState ) =>
			uniq( [ ...prevState, currentRoute ] )
		);
	}, [ currentRoute ] );

	// Update routes with navigation state.
	const routesWithNavigationState = useMemo(
		() =>
			getRoutesWithNavigationState( routes, visitedRoutes, updateRoute ),
		[ routes, visitedRoutes ]
	);

	return (
		<div className="sensei-onboarding__header">
			<Stepper
				steps={ routesWithNavigationState }
				currentStep={ currentRoute }
			/>
		</div>
	);
};

export default Navigation;
