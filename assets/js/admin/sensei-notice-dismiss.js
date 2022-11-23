/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

domReady( () => {
	document.body.addEventListener( 'click', ( event ) => {
		const element = event.target.closest( '.sensei-notice' );
		if (
			! element ||
			! element.dataset.dismissNonce ||
			! element.dataset.dismissAction
		) {
			return;
		}

		if ( event.target.classList.contains( 'notice-dismiss' ) ) {
			const formData = new FormData();
			if ( element.dataset.dismissNotice ) {
				formData.append( 'notice', element.dataset.dismissNotice );
			}
			formData.append( 'action', element.dataset.dismissAction );
			formData.append( 'nonce', element.dataset.dismissNonce );

			fetch( ajaxurl, {
				method: 'POST',
				body: formData,
			} );
		}
	} );
} );
