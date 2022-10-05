/**
 * WordPress dependencies
 */
import domReady from '@wordpress/dom-ready';

function setupLessonVideoBlocks() {
	document
		.querySelectorAll( '.sensei-course-theme-lesson-video' )
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
 * @param {HTMLElement} block Container to track. Must have an <iframe> width and height attributes.
 */
function updateElementHeightOnResize( block ) {
	const getVideoElement = () => block.querySelector( 'iframe' );
	let element = getVideoElement();
	const ratio = element && getAspectRatio( element );

	if ( ! ratio ) {
		return;
	}

	new window.ResizeObserver( resizeElement ).observe( block );
	resizeElement();

	function resizeElement() {
		element = getVideoElement();

		if ( ! element ) {
			return;
		}

		const { offsetHeight, offsetWidth } = element;
		const height = offsetWidth / ratio;

		if ( ! height || height === offsetHeight ) {
			return;
		}

		element.style.setProperty( 'height', `${ height }px` );
	}
}

domReady( setupLessonVideoBlocks );
