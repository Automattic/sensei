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
 * Usage tracking selector.
 *
 * @param {Object} state Current state.
 *
 * @return {boolean} Usage tracking value.
 */
export const getUsageTracking = ( state ) => state.data.welcome.usage_tracking;

export const getStepData = ( state, step ) => state.data[ step ];
