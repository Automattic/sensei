/**
 * Schedule to run action creator after the given time.
 *
 * @param {Function} action Action creator to dispatch.
 * @param {number} timeout Timeout in milliseconds.
 */
export function* schedule( action, timeout ) {
	yield { type: 'SCHEDULE', timeout };
	yield action();
}

/**
 * Clear current timeout.
 */
export function clearSchedule() {
	return { type: 'CLEAR_SCHEDULE' };
}

/**
 * Manage timeout reference.
 */
const scheduledTimeout = {
	current: null,
	/**
	 * Create a new timeout promise.
	 *
	 * @param {number} timeout Timeout in ms.
	 * @return {Promise} Promise resolved after the timeout.
	 */
	create( timeout ) {
		return new Promise( ( resolve ) => {
			scheduledTimeout.clear();
			scheduledTimeout.current = setTimeout( () => {
				resolve();
			}, timeout );
		} );
	},
	/**
	 * Clear current scheduled timeout.
	 */
	clear() {
		if ( scheduledTimeout.current ) {
			clearTimeout( scheduledTimeout.current );
			scheduledTimeout.current = null;
		}
	},
};

export default {
	SCHEDULE: ( { timeout } ) => scheduledTimeout.create( timeout ),
	CLEAR_SCHEDULE: () => scheduledTimeout.clear(),
};
