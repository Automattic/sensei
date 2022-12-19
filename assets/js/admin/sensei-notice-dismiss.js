/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

domReady( () => {
	const hiddenClassName = 'sensei-notice--is-hidden';

	/**
	 * Handle tasks present on the element if the element has the attribute "data-sensei-notice-tasks".
	 *
	 * @param  event The event to handle.
	 */
	const handleTasks = ( event ) => {
		const { target } = event;
		if ( ! target.dataset.senseiNoticeTasks ) {
			return;
		}
		const tasks = JSON.parse( target.dataset.senseiNoticeTasks );
		if ( ! tasks ) {
			return;
		}
		for ( const task of tasks ) {
			const noticeDom =
				task.notice_id &&
				document.querySelector(
					`.sensei-notice[data-sensei-notice-id="${ task.notice_id }"]`
				);
			switch ( task.type ) {
				case 'preventDefault':
					event.preventDefault();
					break;
				case 'show':
					noticeDom?.classList.remove( hiddenClassName );
					break;
				case 'dismiss':
					if ( noticeDom ) {
						handleDismiss( noticeDom );
					}
				//  We need to also hide the notice being dismissed:
				// eslint-disable-next-line no-fallthrough
				case 'hide':
					noticeDom?.classList.add( hiddenClassName );
					break;
			}
		}
	};

	/**
	 * Handle dismissing the notice by sending a request to the server.
	 *
	 * @param  element The DOM element of the container of the notice being dismissed.
	 */
	const handleDismiss = ( element ) => {
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
	};

	document.body.addEventListener( 'click', ( event ) => {
		const noticeContainer = event.target.closest( '.sensei-notice' );
		if ( ! noticeContainer ) {
			return;
		}

		if (
			noticeContainer.dataset.dismissNonce &&
			noticeContainer.dataset.dismissAction &&
			event.target.classList.contains( 'notice-dismiss' )
		) {
			handleDismiss( noticeContainer );
		} else {
			handleTasks( event );
		}
	} );
} );
