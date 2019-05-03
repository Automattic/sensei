window.sensei_log_event = function( event_name, properties ) {
	if ( sensei_event_logging.enabled ) {
		let data = {
			action: 'sensei_log_event',
			event_name: event_name,
		};

		if ( properties ) {
			data.properties = properties;
		}

		jQuery.get( ajaxurl, data );
	}
}
