import { Stepper } from '@woocommerce/components';
import { useQueryStringRouter } from '../query-string-router';

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
 */
const Navigation = ( { steps } ) => {
	const { currentRoute, goTo } = useQueryStringRouter();
	steps = addClickHandlers( steps, { goTo } );

	return <Stepper steps={ steps } currentStep={ currentRoute } />;
};

export default Navigation;
