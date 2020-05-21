
export const logEvent = ( eventName, properties ) => {
	window.sensei_log_event( eventName, properties );
};
