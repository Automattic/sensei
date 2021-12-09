/**
 * @modulw sensei-modal
 * @description Adds a basic suport for modals via "data-sensei-modal-*" attribures on HTML elements.
 *
 * @usage
 * The Sensei Modal consists of four basic elements: open, close, content and overlay.
 * Each of those elements should be denoted with HTML attributes:
 * - data-sensei-modal-open
 * - data-sensei-modal-close
 * - data-sensei-modal-content
 * - data-sensei-modal-overlay
 * The attribute values should be a unique id for each modal. Should look like this:
 * ```html
 * <button data-sensei-modal-open="123">Open Modal</button>
 * <div data-sensei-modal-overlay="123"></div>
 * <div data-sensei-modal-content="123">
 *   <h1>Hello Modal!</h1>
 *   <button data-sensei-modal-close="123">Close Modal</button>
 * </div>
 * ```
 */

/**
 * Creates a modal opener handler.
 * @param {string} modalId The id of the modal.
 */
const createOpenModal = ( modalId ) => ( ev ) => {
	ev?.preventDefault();

	document
		.querySelector( `[data-sensei-modal-content="${ modalId }"]` )
		?.setAttribute( 'data-sensei-modal-content-is-open', '' );
	document
		.querySelector( `[data-sensei-modal-overlay="${ modalId }"]` )
		?.setAttribute( 'data-sensei-modal-overlay-is-open', '' );
};

/**
 * Creates a modal closer handler.
 * @param {string} modalId The id of theh modal.
 */
const createCloseModal = ( modalId ) => ( ev ) => {
	ev?.preventDefault();

	document
		.querySelector( `[data-sensei-modal-overlay="${ modalId }"]` )
		?.removeAttribute( 'data-sensei-modal-overlay-is-open' );
	document
		.querySelector( `[data-sensei-modal-content="${ modalId }"]` )
		?.removeAttribute( 'data-sensei-modal-content-is-open' );
};

/**
 * Attach modal events.
 */
function attachModalEvents() {
	// Attach open events.
	document
		.querySelectorAll( '[data-sensei-modal-open]' )
		.forEach( ( openButton ) => {
			const modalId = openButton.getAttribute( 'data-sensei-modal-open' );
			if ( ! modalId ) {
				return;
			}
			openButton.addEventListener( 'click', createOpenModal( modalId ) );
		} );

	// Attach close events.
	document
		.querySelectorAll( '[data-sensei-modal-overlay]' )
		.forEach( ( modalOverlay ) => {
			const modalId = modalOverlay.getAttribute(
				'data-sensei-modal-overlay'
			);
			if ( ! modalId ) {
				return;
			}
			modalOverlay.addEventListener(
				'click',
				createCloseModal( modalId )
			);
		} );
	document
		.querySelectorAll( '[data-sensei-modal-close]' )
		.forEach( ( closeButton ) => {
			const modalId = closeButton.getAttribute(
				'data-sensei-modal-close'
			);
			if ( ! modalId ) {
				return;
			}
			closeButton.addEventListener(
				'click',
				createCloseModal( modalId )
			);
		} );

	document
		.querySelectorAll( '[data-sensei-modal-open]' )
		.forEach( ( openButton ) => {
			const modalId = openButton.getAttribute( 'data-sensei-modal-open' );
			if ( ! modalId ) {
				return;
			}
			openButton.addEventListener( 'keydown', ( ev ) => {
				if ( 'Escape' === ev.key ) {
					createCloseModal( modalId )();
				}
			} );
		} );
}

// Init modal when the DOM is fully ready.
// eslint-disable-next-line @wordpress/no-global-event-listener
window.addEventListener( 'load', attachModalEvents );
