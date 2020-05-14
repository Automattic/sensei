/**
 * Usage tracking selector.
 *
 * @param {Object} state Current state.
 *
 * @return {boolean} Usage tracking value.
 */
export const getUsageTracking = ( state ) => state.welcome.data.usageTracking;

/**
 * Is fetching usage tracking selector.
 *
 * @param {Object} state Current state.
 *
 * @return {boolean} Is fetching.
 */
export const isFetchingUsageTracking = ( state ) => state.welcome.isFetching;

/**
 * Is submitting usage tracking selector.
 *
 * @param {Object} state Current state.
 *
 * @return {boolean} Is submitting.
 */
export const isSubmittingUsageTracking = ( state ) =>
	state.welcome.isSubmitting;

/**
 * Error usage tracking selector.
 *
 * @param {Object} state Current state.
 *
 * @return {string|boolean} Error message or false.
 */
export const errorUsageTracking = ( state ) => state.welcome.error;
