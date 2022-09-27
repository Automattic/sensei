/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

function setupLessonVideoIframes() {
	document
		.querySelectorAll( '.sensei-learning-mode-lesson-video iframe' )
		.forEach( updateElementHeightOnResize );
}

/**
 * Get aspect ratio from element width and height attribute.
 *
 * @param {HTMLElement} element
 * @param {string}      element.width
 * @param {string}      element.height
 * @return {null|number} Width / Height aspect ratio.
 */
function getAspectRatio( { width, height } ) {
	if ( ! height || ! width ) {
		return null;
	}
	return +width / +height;
}

/**
 * Update video height when its width changes to keep original aspect ratio.
 *
 * @param {HTMLElement} element Element to track. Must have width and height attributes.
 */
function updateElementHeightOnResize( element ) {
	const ratio = getAspectRatio( element );

	const observer = new window.ResizeObserver( resizeElement );
	observer.observe( element );

	function resizeElement() {
		const { offsetHeight, offsetWidth } = element;
		const height = offsetWidth / ratio;

		if ( ! height || height === offsetHeight ) {
			return;
		}

		element.setAttribute( 'height', offsetWidth / ratio );
	}
}

domReady( setupLessonVideoIframes );
