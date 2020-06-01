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
