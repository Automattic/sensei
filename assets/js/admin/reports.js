/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

domReady( () => {
	jQuery(
		'.sensei-analysis__top-filters, .sensei-analysis__inner-filters'
	).on( 'change', ( event ) => {
		event.currentTarget.submit();
	} );
} );
