/**
 * Attaches window.onload event, but also preserves a previously
 * attached handler if there was any.
 *
 * @param {Function} cb The callback to invoke for window.onload event.
 */
export function onWindowLoad( cb ) {
	const originalCb = window.onload;
	window.onload = function () {
		if ( 'function' === typeof originalCb ) {
			originalCb();
		}

		if ( 'function' === typeof cb ) {
			cb();
		}
	};
}
