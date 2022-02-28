/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

domReady( () => {
	jQuery( '.sensei-analysis__inner-form' ).on( 'change', ( event ) => {
		event.currentTarget.submit();
	} );
} );
