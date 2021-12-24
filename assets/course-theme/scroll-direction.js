let lastScrollTop = 0;

const SCROLL_CLASS = 'scroll';

/**
 * Detect if a scroll movement is upward or downward.
 */
const detectScrollDirection = () => {
	const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
	const delta = scrollTop - lastScrollTop;
	lastScrollTop = Math.max( 0, scrollTop );
	setScrollDirection( delta );

	const atBottom = scrollHeight - scrollTop - clientHeight < 100;

	document.body.classList.toggle( `${ SCROLL_CLASS }-bottom`, atBottom );
};

/**
 * Set the `scroll-up` or `scroll-down` class on the body based on scroll direction.
 *
 * @param {number} delta Scroll movement.
 */
const setScrollDirection = ( delta ) => {
	const [ direction, opposite ] =
		delta < 0 ? [ 'up', 'down' ] : [ 'down', 'up' ];

	document.body.classList.remove( `${ SCROLL_CLASS }-${ opposite }` );
	document.body.classList.add( `${ SCROLL_CLASS }-${ direction }` );
};

// eslint-disable-next-line @wordpress/no-global-event-listener
window.addEventListener( 'scroll', detectScrollDirection, {
	capture: false,
	passive: true,
} );
