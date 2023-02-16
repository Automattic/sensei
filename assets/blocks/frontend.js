/**
 * Internal dependencies
 */
import '../js/sensei-modal';

// eslint-disable-next-line @wordpress/no-global-event-listener
window.addEventListener( 'load', () => {
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

		if ( content.classList.contains( 'sensei-collapsed' ) ) {
			const transition = content.style.transition;
			content.style.transition = 'unset';
			content.style.maxHeight = 'unset';
			originalHeight = content.offsetHeight + 'px';
			content.style.visibility = 'hidden';
			content.style.maxHeight = 0;
			content.style.transition = transition;
		} else {
			content.style.maxHeight = originalHeight;
		}

		toggleButton.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			const collapsed = content.classList.toggle( 'sensei-collapsed' );
			toggleButton.classList.toggle( 'sensei-collapsed', collapsed );
			toggleButton.setAttribute( 'aria-expanded', ! collapsed );

			if ( ! collapsed ) {
				content.style.visibility = '';
				content.style.maxHeight = originalHeight;
			} else {
				content.style.maxHeight = '0px';
			}
		} );

		content.addEventListener( 'transitionend', ( e ) => {
			if (
				'max-height' === e.propertyName &&
				content.classList.contains( 'sensei-collapsed' )
			) {
				content.style.visibility = 'hidden';
			}
		} );
	} );
} );
