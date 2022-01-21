/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

domReady( () => {
	jQuery( '.sensei-show-more' ).on( 'click', function ( event ) {
		event.preventDefault();

		jQuery( this ).addClass( 'hidden' ).siblings().removeClass( 'hidden' );
	} );
} );
