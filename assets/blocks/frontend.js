/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

/**
 * Internal dependencies
 */
import '../js/sensei-modal';

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
			const transition = content.style.transition;
			content.style.transition = 'unset';
			content.style.maxHeight = 'unset';
			originalHeight = content.offsetHeight + 'px';
			content.style.maxHeight = 0;
			content.style.transition = transition;
		} else {
			content.style.maxHeight = originalHeight;
		}

		toggleButton.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			toggleButton.classList.toggle( 'collapsed' );
			const collapsed = content.classList.toggle( 'collapsed' );

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
				content.classList.contains( 'collapsed' )
			) {
				content.style.visibility = 'hidden';
			}
		} );
	} );
} );
