/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

domReady( () => {
	const $postsFilterForm = jQuery( '#posts-filter' );

	// Move the search box next to the other actions for easier styling.
	jQuery( '.search-box', $postsFilterForm ).prependTo(
		'.tablenav.top',
		$postsFilterForm
	);
} );
