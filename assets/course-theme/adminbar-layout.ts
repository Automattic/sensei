/**
 * External dependencies
 */
import debounce from 'lodash/debounce';

/**
 * Track how much space the WordPress admin bar takes up at the top of the screen.
 * Updates a CSS variable with the value.
 */
const trackAdminbarOffset = () => {
	const adminbar = document.querySelector( '#wpadminbar' );
	if ( ! adminbar ) {
		return;
	}

	updateAdminbarOffset();
	// eslint-disable-next-line @wordpress/no-global-event-listener
	window.addEventListener( 'scroll', updateAdminbarOffset, {
		capture: false,
		passive: true,
	} );

	/**
	 * The debounce has 2 reasons here:
	 * 1. Reduce the number of times we call the function in a resize.
	 * 2. The admin bar contains an animated transition, so this transition
	 *    needs to be completed in order to make the correct calc.
	 */
	// eslint-disable-next-line @wordpress/no-global-event-listener
	window.addEventListener( 'resize', debounce( updateAdminbarOffset, 500 ) );

	function updateAdminbarOffset() {
		if ( ! adminbar ) {
			return;
		}
		const { top, height } = adminbar.getBoundingClientRect();
		const offset = Math.max( 0, height + top );
		document.documentElement.style.setProperty(
			'--sensei-wpadminbar-offset',
			offset + 'px'
		);
	}
};

// eslint-disable-next-line @wordpress/no-global-event-listener
window.addEventListener( 'DOMContentLoaded', trackAdminbarOffset );
