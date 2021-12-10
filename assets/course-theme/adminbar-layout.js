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

	function updateAdminbarOffset() {
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
