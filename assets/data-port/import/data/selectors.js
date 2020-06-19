import { steps } from '../steps';
import { levels } from '../levels';

/**
 * Is fetching importer data selector.
 *
 * @param {Object} state Current state.
 *
 * @return {boolean} Is fetching.
 */
export const isFetching = ( state ) => state.isFetching;

/**
 * Fetch importer error selector.
 *
 * @param {Object} state Current state.
 *
 * @return {Object|boolean} Error object or false.
 */
export const getFetchError = ( state ) => state.fetchError;

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
export const getStepData = ( state, step ) => state[ step ];

/**
 * Get navigation steps with their state.
 *
 * @param {Object} state current state.
 *
 * @return {Array} Navigation steps.
 */
export const getNavigationSteps = ( { completedSteps } ) => {
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
 * @param {Object} state Current state.
 * @param {string} step  Step name.
 *
 * @return {boolean} Step complete.
 */
export const isCompleteStep = ( { completedSteps }, step ) =>
	completedSteps.includes( step );

/**
 * Get whether the importer is ready to start.
 *
 * @param {Object} state Current state.
 *
 * @return {boolean} If the importer is ready.
 */
export const isReadyToStart = ( state ) =>
	levels
		.map( ( { key } ) => state.upload[ key ] )
		.some( ( level ) => level.isUploaded && ! level.inProgress );
