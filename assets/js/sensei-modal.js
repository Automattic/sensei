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

	[ 'content', 'overlay' ].forEach( ( type ) => {
		const modalElement = document.querySelector(
			`[data-sensei-modal-${ type }="${ modalId }"]`
		);
		if ( ! modalElement ) {
			return;
		}

		// Put element's copy at the end of the body element.
		const modalElementCopy = modalElement.cloneNode( true );
		modalElementCopy.setAttribute(
			`data-sensei-modal-${ type }-clone`,
			modalId
		);
		document.body.appendChild( modalElementCopy );

		// Get the close event handler.
		const closeModal = createCloseModal( modalId );

		// Attach close event for the overlay.
		if ( 'overlay' === type ) {
			modalElementCopy.addEventListener( 'click', closeModal );
		}

		// Attach close event to close button in case it is inside the
		// modal content.
		if ( 'content' === type ) {
			modalElementCopy
				.querySelector( `[data-sensei-modal-close="${ modalId }"]` )
				?.addEventListener( 'click', closeModal );

			// Dispatch open event.
			document.body.dispatchEvent(
				new CustomEvent( 'sensei-modal-open', { detail: modalId } )
			);
		}

		// Open the modal.
		setTimeout(
			() => {
				modalElementCopy.setAttribute(
					`data-sensei-modal-${ type }-is-open`,
					''
				);
			},

			// Make sure the elements are opened only after they are painted by
			// the browser first. Otherwise the transition effects do not work.
			20
		);
	} );
};

/**
 * Creates a modal closer handler.
 * @param {string} modalId The id of theh modal.
 */
const createCloseModal = ( modalId ) => ( ev ) => {
	ev?.preventDefault();

	[ 'overlay', 'content' ].forEach( ( type ) => {
		document
			.querySelector(
				`[data-sensei-modal-${ type }-clone="${ modalId }"]`
			)
			?.remove();

		if ( 'content' === type ) {
			// Dispatch close event.
			document.body.dispatchEvent(
				new CustomEvent( 'sensei-modal-close', { detail: modalId } )
			);
		}
	} );
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

	// Attach close event on Escape key.
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
