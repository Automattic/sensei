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

/**
 * Step state selector.
 *
 * @param {Object} state Current state.
 * @param {string} step Step name.
 *
 * @return {Object} Step data.
 */
export const getStepData = ( state, step ) => state.data[ step ];

/**
 * Get navigation steps with their state.
 *
 * @param {Object} state current state.
 * @param {Array}  steps List of steps.
 *
 * @return {Array} Navigation steps.
 */
export const getNavigationSteps = ( { data: { completedSteps } }, steps ) => {
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
