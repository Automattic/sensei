/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

domReady( () => {
	jQuery( '.sensei-date-picker' ).datepicker( {
		dateFormat: 'yy-mm-dd',
	} );

	const timezone = Intl?.DateTimeFormat()?.resolvedOptions()?.timeZone;
	if ( timezone ) {
		jQuery( '.sensei-analysis__top-filters input[name="timezone"]' ).val(
			timezone
		);
	}
} );
