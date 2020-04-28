import { useState, useEffect, useMemo } from '@wordpress/element';
import { Stepper } from '@woocommerce/components';
import { get, uniq } from 'lodash';

import { useQueryStringRouter } from '../query-string-router';

/**
 * Merge the navigation state into the steps.
 * Add isComplete and onClick - when visited.
 *
 * @param {Array}    steps        Steps list.
 * @param {string[]} visitedSteps Key of the visited steps.
 * @param {Function} updateRoute  Function that update the step.
 */
const getStepsWithNavigationState = ( steps, visitedSteps, updateRoute ) =>
	steps.map( ( step, index ) => {
		const nextKey = get( steps, [ index + 1, 'key' ], null );

		const stepWithNavigationState = {
			...step,
			isComplete: visitedSteps.includes( nextKey ),
		};

		if ( visitedSteps.includes( step.key ) ) {
			stepWithNavigationState.onClick = () => {
				updateRoute( step.key );
			};
		}

		return stepWithNavigationState;
	} );

/**
 * Navigation component.
 */
const Navigation = ( { steps } ) => {
	const { currentRoute, updateRoute } = useQueryStringRouter();

	// Visited steps.
	const [ visitedSteps, setVisitedSteps ] = useState( [] );

	useEffect( () => {
		setVisitedSteps( ( prevState ) =>
			uniq( [ ...prevState, currentRoute ] )
		);
	}, [ currentRoute ] );

	// Update steps with navigation state.
	const stepsWithNavigationState = useMemo(
		() =>
			getStepsWithNavigationState( steps, visitedSteps, updateRoute ),
		[ steps, visitedSteps ]
	);

	return (
		<Stepper
			steps={ stepsWithNavigationState }
			currentStep={ currentRoute || steps[ 0 ].key }
		/>
	);
};

export default Navigation;
