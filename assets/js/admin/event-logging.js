// Used to add tracking to WP core elements, where we can't add custom code.
const adminTracking = [
	{
		selector:
			'#toplevel_page_sensei a[href="admin.php?page=sensei_import"]',
		eventName: 'import_click',
	},
	{
		selector:
			'#toplevel_page_sensei a[href="admin.php?page=sensei_export"]',
		eventName: 'export_click',
	},
];

window.sensei_log_event = function ( event_name, properties ) {
	const actionName = 'sensei_log_event';

	if ( ! sensei_event_logging.enabled ) {
		return;
	}

	if ( navigator.sendBeacon ) {
		const formData = new FormData();

		formData.append( 'action', actionName );
		formData.append( 'event_name', event_name );

		if ( properties ) {
			formData.append( 'properties', JSON.stringify( properties ) );
		}

		navigator.sendBeacon( ajaxurl, formData );
		return;
	}

	let data = {
		action: actionName,
		event_name: event_name,
	};

	if ( properties ) {
		data.properties = properties;
	}

	jQuery.get( ajaxurl, data );
};

jQuery( document ).ready( function ( $ ) {
	adminTracking.forEach( function ( tracking ) {
		$( tracking.selector ).attr(
			'data-sensei-log-event',
			tracking.eventName
		);
	} );

	$( 'body' ).on( 'click', 'a[data-sensei-log-event]', function ( event ) {
		let sensei_event_name = $( event.target ).data( 'sensei-log-event' );
		sensei_log_event( sensei_event_name );
	} );
} );
