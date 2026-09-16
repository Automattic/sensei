/**
 * Used to add tracking to WP core elements, where we can't add custom code.
 * Settings and Extensions submenus are logged elsewhere.
 */
const selector = '#menu-posts-course ';
const adminTracking = [
	{
		selector: selector + '.wp-menu-name',
		eventName: 'courses_view',
	},
	{
		selector: selector + 'a[href="edit.php?post_type=course"]',
		eventName: 'courses_view',
	},
	{
		selector:
			selector +
			'a[href="edit-tags.php?taxonomy=module&post_type=course"]',
		eventName: 'modules_view',
	},
	{
		selector: selector + 'a[href="edit.php?post_type=lesson"]',
		eventName: 'lessons_view',
	},
	{
		selector: selector + 'a[href="edit.php?post_type=question"]',
		eventName: 'questions_view',
	},
	{
		selector: selector + 'a[href="admin.php?page=sensei_learners"]',
		eventName: 'student_management_view',
	},
	{
		selector: selector + 'a[href="admin.php?page=sensei_grading"]',
		eventName: 'grading_view',
	},
	{
		selector: selector + 'a[href="edit.php?post_type=sensei_message"]',
		eventName: 'messages_view',
	},
	{
		selector: selector + 'a[href="admin.php?page=sensei_reports"]',
		eventName: 'analysis_view',
	},
	{
		selector: selector + 'a[href="admin.php?page=sensei-tools"]',
		eventName: 'tools_view',
	},
];

window.sensei_log_event = function ( eventName, properties ) {
	const actionName = 'sensei_log_event';

	if ( ! sensei_event_logging.enabled ) {
		return;
	}

	if ( navigator.sendBeacon ) {
		const formData = new FormData();

		formData.append( 'action', actionName );
		formData.append( 'event_name', eventName );

		if ( properties ) {
			formData.append( 'properties', JSON.stringify( properties ) );
		}

		navigator.sendBeacon( ajaxurl, formData );
		return;
	}

	const data = {
		action: actionName,
		event_name: eventName,
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
		const senseiEventName = $( event.target ).data( 'sensei-log-event' );
		sensei_log_event( senseiEventName );
	} );
} );
