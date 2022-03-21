/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

domReady( () => {
	jQuery( '.sensei-date-picker' ).datepicker( {
		dateFormat: 'yy-mm-dd',
	} );
} );
