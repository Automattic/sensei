/**
 * External dependencies
 */
import debounce from 'lodash/debounce';

/**
 * The last scroll top value.
 *
 * @member {number}
 */
let lastScrollTop = 0;

/**
 * Detect if a scroll movement is upward or downward.
 */
const getScrollDelta = () => {
	const { scrollTop } = document.documentElement;
	const delta = scrollTop - lastScrollTop;
	lastScrollTop = Math.max( 0, scrollTop );
	return delta;
};

/**
 * Tells if the sidebar is supposed to be sticky.
 *
 * @return {boolean} True if it is sticky. False otherwise.
 */
const isStickySidebar = () =>
	[ 'modern', 'video-full' ].some( ( templateName ) =>
		document.body.classList.contains( `learning-mode--${ templateName }` )
	);

/**
 * Sidebar margin top.
 *
 * @member {number}
 */
let sidebarMarginTop = 0;

/**
 * The sidebar DOM element.
 *
 * @member {HTMLElement}
 */
let sidebar = null;

/**
 * The clone of the sidebar DOM element.
 *
 * @member {HTMLElement}
 */
let sidebarClone = null;

function prepareSidebarClone() {
	sidebar = document.querySelector( '.sensei-course-theme__sidebar' );
	const sidebarRect = sidebar.getBoundingClientRect();
	sidebarMarginTop = sidebar.style.marginTop
		? parseInt( sidebar.style.marginTop, 10 )
		: 0;
	if ( sidebarClone?.remove ) {
		sidebarClone.remove();
	}
	sidebarClone = sidebar.cloneNode( true );
	sidebarClone.style.position = 'fixed';
	sidebarClone.style.opacity = 1;
	sidebarClone.style.zIndex = 2;
	sidebarClone.style.top = `${ sidebarRect.top }px`;
	sidebarClone.style.left = `${ sidebarRect.left }px`;
	sidebarClone.style.width = `${ sidebarRect.right - sidebarRect.left }px`;
	sidebarClone.style.transition = 'none';
	sidebar.parentElement.append( sidebarClone );
	sidebar.style.opacity = 0;
}

/**
 * Sidebar bottom margin.
 *
 * @member {number}
 */
const SIDEBAR_BOTTOM_MARGIN = 32;

/**
 * Monitors the sidebar position and sticks/unsticks it when needed.
 *
 * @param {boolean} initialPosition True if the sidebar should be positioned
 *                                  for it's initial position given the current
 *                                  state of the scrollbar. Used when user opens
 *                                  the page and it is scrolled into the middle.
 */
function updateSidebarPosition( initialPosition = false ) {
	if ( ! sidebar || ! sidebarClone ) {
		return;
	}
	const header = document.querySelector( '.sensei-course-theme__header' );
	const headerRect = header.getBoundingClientRect();
	const sidebarRect = sidebar.getBoundingClientRect();
	const sidebarCloneRect = sidebarClone.getBoundingClientRect();
	const delta = getScrollDelta();
	let sidebarCloneNewTop = sidebarRect.top;
	const sidebarCloneHeight = sidebarCloneRect.bottom - sidebarCloneRect.top;
	const sidebarCloneIsTallerThanViewport =
		sidebarCloneHeight >
		window.innerHeight -
			( headerRect.bottom + sidebarMarginTop + SIDEBAR_BOTTOM_MARGIN );

	if ( sidebarCloneIsTallerThanViewport && ! initialPosition ) {
		sidebarCloneNewTop = sidebarCloneRect.top - delta;
		const sidebarCloneNewBottom = sidebarCloneRect.bottom - delta;
		const sidebarCloneMinTop = sidebarRect.top;
		const sidebarCloneMinBottom =
			window.innerHeight - SIDEBAR_BOTTOM_MARGIN;
		// The sidebar is moving upwards.
		if ( delta >= 0 ) {
			if ( sidebarCloneNewBottom < sidebarCloneMinBottom ) {
				sidebarCloneNewTop = sidebarCloneMinBottom - sidebarCloneHeight;
			}
		} else {
			if ( sidebarCloneNewTop > headerRect.bottom ) {
				sidebarCloneNewTop = headerRect.bottom;
			}
			if ( sidebarCloneNewTop < sidebarCloneMinTop ) {
				sidebarCloneNewTop = sidebarCloneMinTop;
			}
		}
	} else if ( sidebarRect.top <= headerRect.bottom ) {
		sidebarCloneNewTop = headerRect.bottom;
	} else {
		sidebarCloneNewTop = sidebarRect.top;
	}

	sidebarClone.style.top = `${ sidebarCloneNewTop - sidebarMarginTop }px`;
}

/**
 * Makes the sidebar sticky for relevant LM templates.
 */
function stickySidebar() {
	if ( ! isStickySidebar() ) {
		return;
	}

	prepareSidebarClone();
	updateSidebarPosition( true );

	document.defaultView.addEventListener( 'scroll', () =>
		updateSidebarPosition()
	);

	// eslint-disable-next-line @wordpress/no-global-event-listener
	window.addEventListener(
		'resize',
		debounce( () => {
			prepareSidebarClone();
			updateSidebarPosition( true );
		}, 500 )
	);
}

// eslint-disable-next-line @wordpress/no-global-event-listener
window.addEventListener( 'DOMContentLoaded', stickySidebar );
