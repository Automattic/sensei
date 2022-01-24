/**
 * Focus mode class name and session storage key.
 *
 * @type {string}
 */
const FOCUS_MODE_CLASS = 'sensei-course-theme--focus-mode';

/**
 * Initialize focus mode state on page load.
 */
const initFocusMode = () => {
	restoreFocusModeState();
	setTimeout( () => {
		document.body.classList.add( `${ FOCUS_MODE_CLASS }--animated` );
	}, 500 );
};

/**
 * Restore previous state.
 */
const restoreFocusModeState = () => {
	const savedState = window.sessionStorage.getItem( FOCUS_MODE_CLASS );
	if ( ! savedState ) return;
	try {
		const wasActive = JSON.parse( savedState );
		if ( 'boolean' === typeof wasActive ) {
			toggleFocusMode( wasActive, true );
		}
	} catch ( e ) {}
};

/**
 * Toggle focus mode.
 *
 * @param {boolean?} on
 * @param {boolean?} restore Whether restoring.
 */
const toggleFocusMode = ( on, restore ) => {
	const { classList } = document.body;

	const courseNavigation = document.querySelector(
		'.sensei-lms-course-navigation'
	);
	const isActive = classList.contains( FOCUS_MODE_CLASS );
	const next = 'undefined' === typeof on ? ! isActive : on;

	if ( ! next ) {
		courseNavigation.style.visibility = '';
	} else if ( restore ) {
		courseNavigation.style.visibility = 'hidden';
	}

	classList.toggle( FOCUS_MODE_CLASS, next );
	window.sessionStorage.setItem( FOCUS_MODE_CLASS, JSON.stringify( next ) );
};

// eslint-disable-next-line @wordpress/no-global-event-listener
window.addEventListener( 'DOMContentLoaded', () => {
	initFocusMode();

	document
		.querySelector( '.sensei-course-theme__sidebar' )
		.addEventListener( 'transitionend', ( e ) => {
			if (
				'left' === e.propertyName &&
				document.body.classList.contains( FOCUS_MODE_CLASS )
			) {
				document.querySelector(
					'.sensei-lms-course-navigation'
				).style.visibility = 'hidden';
			}
		} );
} );

export { toggleFocusMode };
