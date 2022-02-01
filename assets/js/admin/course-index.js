/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

domReady( () => {
	jQuery( '.sensei-show-more' ).on( 'click', ( event ) => {
		event.preventDefault();

		jQuery( event.target )
			.addClass( 'hidden' )
			.siblings()
			.removeClass( 'hidden' );
	} );
} );
