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
 * @param {Function} goTo         Function that update the step.
 *
 * @return {Object} Steps with navigation state merged.
 */
const getStepsWithNavigationState = ( steps, visitedSteps, goTo ) =>
	steps.map( ( step, index ) => {
		const nextKey = get( steps, [ index + 1, 'key' ], null );

		const stepWithNavigationState = {
			...step,
			isComplete: nextKey && visitedSteps.includes( nextKey ),
		};

		if ( visitedSteps.includes( step.key ) ) {
			stepWithNavigationState.onClick = () => {
				goTo( step.key );
			};
		}

		return stepWithNavigationState;
	} );

/**
 * Navigation component.
 */
const Navigation = ( { steps } ) => {
	const { currentRoute, goTo } = useQueryStringRouter();

	// Visited steps.
	const [ visitedSteps, setVisitedSteps ] = useState( [] );

	useEffect( () => {
		setVisitedSteps( ( prevState ) =>
			uniq( [ ...prevState, currentRoute ] )
		);
	}, [ currentRoute ] );

	// Update steps with navigation state.
	const stepsWithNavigationState = useMemo(
		() => getStepsWithNavigationState( steps, visitedSteps, goTo ),
		[ steps, visitedSteps, goTo ]
	);

	return (
		<Stepper
			steps={ stepsWithNavigationState }
			currentStep={ currentRoute || steps[ 0 ].key }
		/>
	);
};

export default Navigation;
