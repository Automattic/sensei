/**
 * Internal dependencies
 */
import { TourStep } from './types';

export const HIGHLIGHT_CLASS = 'sensei-tour-highlight';

/**
 * Performs the action for the step.
 *
 * @param {number}           index The index of the step.
 * @param {Array.<TourStep>} steps The steps array.
 */
export function performStepAction( index, steps ) {
	if ( index < steps.length ) {
		const step = steps[ index ];
		if ( step.action ) {
			step.action();
		}
	}
}

/**
 * Highlights the elements with a border.
 *
 * @param {Array}  selectors An array of selectors to highlight.
 * @param {string} modifier  A modifier to add to the highlight class.
 */
export function highlightElementsWithBorders( selectors, modifier = '' ) {
	selectors.forEach( function ( selector ) {
		const element = document.querySelector( selector );
		if ( element ) {
			element.classList.add( HIGHLIGHT_CLASS );

			if ( modifier ) {
				element.classList.add( HIGHLIGHT_CLASS + '--' + modifier );
			}
		}
	} );
}

/**
 * Removes the highlight classes from the elements.
 */
export function removeHighlightClasses() {
	const highlightedElements = document.querySelectorAll(
		'.sensei-tour-highlight'
	);
	highlightedElements.forEach( function ( element ) {
		// Remove class and modifiers.
		[ ...element.classList ].forEach( ( className ) => {
			if ( className.startsWith( HIGHLIGHT_CLASS ) ) {
				element.classList.remove( className );
			}
		} );
	} );
}

let stepActionTimeout = null;
let rejectLastPromise = null;

/**
 * Performs step actions one after another.
 *
 * @param {Array} stepActions An array of selectors to highlight.
 */
export async function performStepActionsAsync( stepActions ) {
	removeHighlightClasses();

	// Clear the timeout and reject the last promise if it exists, so it stops the step if actions from another step started.
	clearTimeout( stepActionTimeout );
	if ( rejectLastPromise ) {
		rejectLastPromise();
	}

	try {
		for ( const stepAction of stepActions ) {
			if ( stepAction ) {
				await new Promise( ( resolve, reject ) => {
					rejectLastPromise = reject;
					stepActionTimeout = setTimeout( () => {
						stepAction.action();
						resolve();
					}, stepAction.delay ?? 0 );
				} );
			}
		}
	} catch ( e ) {
		// Do nothing.
	}
}

/**
 * Waits for an element to be available in the DOM.
 *
 * @param {string} selector  The selector to wait for.
 * @param {number} maxChecks The maximum number of checks to perform.
 * @param {number} delay     The delay between checks.
 *
 * @return {Promise<unknown>} A promise that resolves when the element is available.
 */
export async function waitForElement( selector, maxChecks = 10, delay = 300 ) {
	return new Promise( ( resolve, reject ) => {
		let checks = 0;

		function checkElement() {
			const element = document.querySelector( selector );
			if ( element ) {
				resolve( element );
			} else {
				checks++;
				if ( checks >= maxChecks ) {
					reject();
				} else {
					setTimeout( checkElement, delay );
				}
			}
		}

		checkElement();
	} );
}
