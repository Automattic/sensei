/**
 * Internal dependencies
 */
import { steps } from '../steps';

/**
 * Is fetching setup wizard data selector.
 *
 * @param {Object} state Current state.
 *
 * @return {boolean} Is fetching.
 */
export const isFetching = ( state ) => state.isFetching;

/**
 * Fetch setup wizard error selector.
 *
 * @param {Object} state Current state.
 *
 * @return {Object|boolean} Error object or false.
 */
export const getFetchError = ( state ) => state.fetchError;

/**
 * Is submitting setup wizard data selector.
 *
 * @param {Object} state Current state.
 *
 * @return {boolean} Is submitting.
 */
export const isSubmitting = ( state ) => state.isSubmitting;

/**
 * Submit error selector.
 *
 * @param {Object} state Current state.
 *
 * @return {Object|boolean} Error object or false.
 */
export const getSubmitError = ( state ) => state.submitError;

/* eslint-disable jsdoc/check-param-names */
/**
 * Step state selector.
 *
 * @param {Object}  state         Current state.
 * @param {string}  step          Step name.
 * @param {boolean} shouldResolve Flag whether should invoke the resolver.
 *
 * @return {Object} Step data.
 */
/* eslint-enable */
export const getStepData = ( state, step ) => state.data[ step ];

/**
 * Get navigation steps with their state.
 *
 * @param {Object} input                     getNavigationSteps input.
 * @param {Object} input.data                The current state.
 * @param {Array}  input.data.completedSteps The completed steps.
 *
 * @return {Array} Navigation steps.
 */
export const getNavigationSteps = ( { data: { completedSteps } } ) => {
	const navSteps = steps.map( ( step ) => ( {
		...step,
		isComplete: completedSteps.includes( step.key ),
		isNext: false,
	} ) );

	const nextStep =
		navSteps.find( ( step ) => ! step.isComplete ) || navSteps[ 0 ];
	nextStep.isNext = true;

	return navSteps;
};

/**
 * Get whether step is complete or not.
 *
 * @param {Object} input                     getNavigationSteps input.
 * @param {Object} input.data                The current state.
 * @param {Array}  input.data.completedSteps The completed steps.
 * @param {Array}  step                      The step to check if it is completed.
 *
 * @return {boolean} Step complete.
 */
export const isCompleteStep = ( { data: { completedSteps } }, step ) =>
	completedSteps.includes( step );
