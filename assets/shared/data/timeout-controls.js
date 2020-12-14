/**
 * Schedule to run action creator after the given time.
 *
 * @param {Function} action Action creator to dispatch.
 * @param {number}   wait   Timeout in milliseconds.
 */
export function* timeout( action, wait ) {
	yield { type: 'TIMEOUT', wait };
	yield action();
}

/**
 * Clear current timeout.
 */
export function cancelTimeout() {
	return { type: 'CLEAR_TIMEOUT' };
}

/**
 * Manage timeout reference.
 */
const scheduledTimeout = {
	current: null,
	/**
	 * Create a new timeout promise.
	 *
	 * @param {number} wait Timeout in ms.
	 * @return {Promise} Promise resolved after the timeout.
	 */
	create( wait ) {
		return new Promise( ( resolve ) => {
			scheduledTimeout.clear();
			scheduledTimeout.current = setTimeout( () => {
				resolve();
			}, wait );
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
	TIMEOUT: ( { wait } ) => scheduledTimeout.create( wait ),
	CLEAR_TIMEOUT: () => scheduledTimeout.clear(),
};
