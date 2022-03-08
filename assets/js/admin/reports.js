/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

domReady( () => {
	jQuery( '.sensei-analysis__top-filters' ).on( 'change', ( event ) => {
		event.currentTarget.submit();
	} );
} );
