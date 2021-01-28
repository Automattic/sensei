/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

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

		if ( content.classList.contains( 'collapsed' ) ) {
			originalHeight = '100vh';
		} else {
			content.style.maxHeight = originalHeight;
		}

		toggleButton.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			toggleButton.classList.toggle( 'collapsed' );
			const collapsed = content.classList.toggle( 'collapsed' );

			if ( ! collapsed ) {
				content.style.maxHeight = originalHeight;
			} else {
				content.style.maxHeight = '0px';
			}
		} );
	} );
} );
