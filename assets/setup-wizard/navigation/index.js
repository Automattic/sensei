/**
 * Internal dependencies
 */
import { useQueryStringRouter } from '../../shared/query-string-router';
import Stepper from '../../shared/components/stepper';

/**
 * Go to route when clicking steps that can be active (completed or next).
 *
 * @param {Array}    steps
 * @param {Object}   deps
 * @param {Function} deps.goTo
 *
 * @return {Array} Steps with click handlers.
 */
const addClickHandlers = ( steps, { goTo } ) =>
	steps.map( ( step ) => ( {
		...step,
		onClick:
			step.isComplete || step.isNext ? () => goTo( step.key ) : undefined,
	} ) );

/**
 * Navigation component.
 *
 * @param {Object} input       Navigation input.
 * @param {Array}  input.steps The available steps.
 */
const Navigation = ( { steps } ) => {
	const { currentRoute, goTo } = useQueryStringRouter();
	steps = addClickHandlers( steps, { goTo } );

	return <Stepper steps={ steps } currentStep={ currentRoute } />;
};

export default Navigation;
