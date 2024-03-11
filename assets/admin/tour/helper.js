export const HIGHLIGHT_CLASS = 'sensei-tour-highlight';

export function PerformStepAction( index, steps ) {
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
 * @param {Array} selectors An array of selectors to highlight.
 */
export function highlightElementsWithBorders( selectors ) {
	selectors.forEach( function ( selector ) {
		const element = document.querySelector( selector );
		if ( element ) {
			element.classList.add( HIGHLIGHT_CLASS );
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
		element.classList.remove( HIGHLIGHT_CLASS );
	} );
}

/**
 * Performs step actions one after another.
 *
 * @param {Array} stepActions An array of selectors to highlight.
 */
export async function performStepActionsAsync( stepActions ) {
	removeHighlightClasses();

	for ( const stepAction of stepActions ) {
		if ( stepAction ) {
			await new Promise( ( resolve ) =>
				setTimeout( () => {
					stepAction.action();
					resolve();
				}, stepAction.delay ?? 0 )
			);
		}
	}
}
