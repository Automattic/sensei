/**
 * Compose an action creator with the given start, success and error actions.
 *
 * @param {string}   startAction   Start action type.
 * @param {Function} fetchFn       The action creator to be wrapped. Should return the resolved data.
 * @param {string}   successAction Success action type.
 * @param {string}   errorAction   Error action type.
 * @return {Function} The wrapped action creator.
 */
export const composeFetchAction = (
	startAction,
	fetchFn,
	successAction,
	errorAction
) =>
	function*( ...args ) {
		yield { type: startAction };

		try {
			const data = yield* fetchFn( ...args );
			yield { type: successAction, data };
		} catch ( error ) {
			yield { type: errorAction, error };
		}
	};
