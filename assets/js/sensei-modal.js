/* eslint @wordpress/no-global-active-element: 0 -- Not relevant out of React.  */

/**
 * Internal dependencies
 */
import { querySelectorAncestor } from '../shared/helpers/DOM';

/**
 * @module sensei-modal
 * @description Adds a basic suport for modals via "data-sensei-modal-*" attribures on HTML elements.
 *
 * @usage
 * The Sensei Modal consists of four basic elements: open, close, content and overlay.
 * Each of those elements should be denoted with HTML attributes:
 * - data-sensei-modal-open
 * - data-sensei-modal-content
 * - data-sensei-modal-overlay (Optional)
 * - data-sensei-modal-close (Optional)
 * The modal declaration should look like something this:
 * ```html
 * <div data-sensei-modal>
 *   <button data-sensei-modal-open>Open Modal</button>
 *   <div data-sensei-modal-overlay></div>
 *   <div data-sensei-modal-content>
 *     <h1>Hello Modal!</h1>
 *     <button data-sensei-modal-close>Close Modal</button>
 *   </div>
 * </div>
 * ```
 */

/**
 * The last focused element in the document.
 *
 * @type {Element}
 */
let lastActiveElement = document.activeElement;

/**
 * Opens the modal
 * @param {MouseEvent} ev The click event.
 */
const openModal = ( ev ) => {
	ev?.preventDefault();
	const modalElement = querySelectorAncestor(
		ev.target,
		'[data-sensei-modal]'
	);
	if ( ! modalElement ) {
		return;
	}

	// Put element's copy at the end of the body element.
	const modalElementCopy = modalElement.cloneNode( true );
	modalElementCopy.setAttribute( 'data-sensei-modal-clone', '' );
	document.body.appendChild( modalElementCopy );

	[ 'overlay', 'close' ].forEach( ( type ) => {
		modalElementCopy
			.querySelectorAll( `[data-sensei-modal-${ type }]` )
			.forEach( ( closeElement ) => {
				closeElement.addEventListener( 'click', closeModal );
			} );
	} );

	// Open the modal.
	// Make sure the elements are opened only after they are painted by
	// the browser first. Otherwise the transition effects do not work.
	window.requestAnimationFrame( () =>
		window.requestAnimationFrame( () => {
			modalElementCopy.setAttribute( 'data-sensei-modal-is-open', '' );
			document.body.dispatchEvent(
				new CustomEvent( 'sensei-modal-open', {
					detail: modalElementCopy,
				} )
			);
			lastActiveElement = document.activeElement;
			const content = modalElementCopy.querySelector(
				'[data-sensei-modal-content]'
			);
			if ( content ) {
				content.tabIndex = 0;
				content.focus();
			}
		} )
	);
};

/**
 * Closes the opened modal
 * @param {MouseEvent} ev The click event.
 */
const closeModal = ( ev ) => {
	ev?.preventDefault();
	document
		.querySelectorAll( '[data-sensei-modal-clone]' )
		.forEach( ( modalElement ) => {
			modalElement.remove();
			document.body.dispatchEvent(
				new CustomEvent( 'sensei-modal-close', {
					detail: modalElement,
				} )
			);
			lastActiveElement?.focus();
		} );
};

/**
 * Attach modal events.
 */
function attachModalEvents() {
	// Attach open events.
	document
		.querySelectorAll( '[data-sensei-modal-open]' )
		.forEach( ( opener ) => {
			opener.addEventListener( 'click', openModal );
		} );

	// Attach close event on Escape key.
	// eslint-disable-next-line @wordpress/no-global-event-listener
	document.addEventListener( 'keydown', ( ev ) => {
		if ( 'Escape' === ev.key ) {
			closeModal( ev );
		}
	} );
}

// Init modal when the DOM is fully ready.
// eslint-disable-next-line @wordpress/no-global-event-listener
window.addEventListener( 'load', attachModalEvents );

/**
 * Support for closing the Modal on Esc key.
 */
// eslint-disable-next-line @wordpress/no-global-event-listener
document.addEventListener( 'keydown', ( ev ) => {
	if ( [ 'Esc', 'Escape' ].includes( ev.key ) ) {
		closeModal();
	}
} );
