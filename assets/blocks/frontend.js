/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

/**
 * Internal dependencies
 */
import '../js/sensei-modal';

/**
 * The collapse/expand transition duration in milliseconds.
 *
 * @constant {number}
 */
const TRANSITION_DURATION = 350;

domReady( () => {
	if (
		0 === document.querySelectorAll( '.sensei-collapsible__toggle' ).length
	) {
		return;
	}

	const blocks = document.querySelectorAll( '.sensei-collapsible' );

	blocks.forEach( ( block ) => {
		const content = block.querySelector( '.sensei-collapsible__content' );
		const toggleButton = block.querySelector(
			'.sensei-collapsible__toggle'
		);

		if ( ! content || ! toggleButton ) {
			return;
		}

		let originalHeight = content.offsetHeight + 'px';
		const originalDisplay = content.style.display;

		if ( content.classList.contains( 'collapsed' ) ) {
			const transition = content.style.transition;
			content.style.transition = 'unset';
			content.style.maxHeight = 'unset';
			originalHeight = content.offsetHeight + 'px';
			content.style.maxHeight = 0;
			content.style.transition = transition;
			content.style.display = 'none';
		} else {
			content.style.maxHeight = originalHeight;
		}

		let transitionTimeoutId = null;
		toggleButton.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			toggleButton.classList.toggle( 'collapsed' );
			const collapsed = content.classList.toggle( 'collapsed' );

			clearTimeout( transitionTimeoutId );
			if ( ! collapsed ) {
				window.requestAnimationFrame( () => {
					content.style.display = originalDisplay;
					window.requestAnimationFrame( () => {
						content.style.maxHeight = originalHeight;
					} );
				} );
			} else {
				content.style.maxHeight = '0px';

				// At the end of the collapse animation, we set the content
				// display to "none", so the elements inside the content do not
				// get focus when user navigates by tabbing through buttons and links.
				transitionTimeoutId = setTimeout( () => {
					content.style.display = 'none';
				}, TRANSITION_DURATION );
			}
		} );
	} );
} );
