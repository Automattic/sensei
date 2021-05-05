/**
 * Send log event.
 *
 * @param {string} eventName  Event name.
 * @param {Array}  properties Event properties.
 */
export const logEvent = ( eventName, properties ) => {
	window.sensei_log_event( eventName, properties );
};

/**
 * Enable or disable event logging.
 *
 * @param {boolean} enabled Enabled state.
 */
logEvent.enable = ( enabled ) => {
	window.sensei_event_logging.enabled = enabled;
};

/**
 * Send log event when link is opened.
 *
 * @param {string} eventName  Event name.
 * @param {Array}  properties Event properties.
 * @return {Object} Element attributes.
 */
export const logLink = ( eventName, properties ) => {
	const isMiddleButtonEvent = ( e ) => 1 === e.button;
	return {
		onClick: () => logEvent( eventName, properties ),
		onAuxClick: ( e ) =>
			isMiddleButtonEvent( e ) && logEvent( eventName, properties ),
	};
};
