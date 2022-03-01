/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

domReady( () => {
	jQuery( '.sensei-analysis__filter-form' ).on( 'change', ( event ) => {
		event.currentTarget.submit();
	} );
} );
