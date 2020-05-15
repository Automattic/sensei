/**
 * Is fetching setup wizard data selector.
 *
 * @param {Object} state Current state.
 *
 * @return {boolean} Is fetching.
 */
export const isFetchingSetupWizardData = ( state ) => state.isFetching;

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
export const getSubmitError = ( state ) => state.error;

/**
 * Usage tracking selector.
 *
 * @param {Object} state Current state.
 *
 * @return {boolean} Usage tracking value.
 */
export const getUsageTracking = ( state ) => state.data.welcome.usage_tracking;
