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
 * Calculates the scroll delta.
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
 * The header DOM element.
 *
 * @member {HTMLElement}
 */
let header = null;

/**
 * The clone of the sidebar DOM element. This is the sidebar element
 * that user sees and interacts with.
 *
 * @member {HTMLElement}
 */
let stickySidebar = null;

/**
 * The featured video DOM element.
 *
 * @member {HTMLElement}
 */
let featuredVideo = null;

/**
 * Populates the DOM elements that we need.
 */
const queryDomElements = () => {
	sidebar = document.querySelector( '.sensei-course-theme__sidebar' );
	header = document.querySelector( '.sensei-course-theme__header' );
	featuredVideo = document.querySelector(
		'.sensei-course-theme-lesson-video'
	);
};

/**
 * Creates an exact copy of the sidebar DOM element
 * and sets it's position to fixed. The original sidebar
 * element is hidden by seting it's opacity to 0. The clone, "stickySidebar"
 * is used to keep the sidebar sticky. We still need the original sidebar
 * element because we use it's original position to calculate and decide
 * where the stickySideber should be position at any given time.
 *
 * This can be called multiple times and if it detects an existing stickySidebar
 * present in the DOM it will remove it and insert the new one.
 */
function preparestickySidebar() {
	const sidebarRect = sidebar.getBoundingClientRect();
	sidebarMarginTop = sidebar.style.marginTop
		? parseInt( sidebar.style.marginTop, 10 )
		: 0;
	if ( stickySidebar?.remove ) {
		stickySidebar.remove();
	}
	stickySidebar = sidebar.cloneNode( true );
	stickySidebar.style.position = 'fixed';
	stickySidebar.style.opacity = 1;
	stickySidebar.style.zIndex = 2;
	stickySidebar.style.top = `${ sidebarRect.top }px`;
	stickySidebar.style.left = `${ sidebarRect.left }px`;
	stickySidebar.style.width = `${ sidebarRect.right - sidebarRect.left }px`;
	stickySidebar.style.transition = 'none';
	sidebar.parentElement.append( stickySidebar );
	sidebar.style.opacity = 0;
}

/**
 * Sidebar bottom margin.
 *
 * @member {number}
 */
const SIDEBAR_BOTTOM_MARGIN = 32;

/**
 * Updates the stickySidebar position. The position of the stickySidebar
 * is relative to the Learning Mode header block. It assumes the header is
 * fixed.
 *
 * @param {boolean} initialPosition True if the sidebar should be positioned
 *                                  for it's initial position given the current
 *                                  state of the scrollbar. Used when user opens
 *                                  the page and the page is scrolled into the middle.
 */
function updateSidebarPosition( initialPosition = false ) {
	if ( ! sidebar || ! stickySidebar ) {
		return;
	}

	// Get the current dimensions of the elements.
	const headerRect = header.getBoundingClientRect();
	const sidebarRect = sidebar.getBoundingClientRect();
	const stickySidebarRect = stickySidebar.getBoundingClientRect();

	// Calculate required values.
	const delta = getScrollDelta();
	const stickySidebarHeight =
		stickySidebarRect.bottom - stickySidebarRect.top;
	const stickySidebarIsTallerThanViewport =
		stickySidebarHeight >
		window.innerHeight -
			( headerRect.bottom + sidebarMarginTop + SIDEBAR_BOTTOM_MARGIN );
	let stickySidebarNewTop = sidebarRect.top;

	// If the sidebar is very tall and does not fit into the viewport vertically
	// we scroll the sticky sidebar up until the bottom is reached. Or we scroll
	// the sticky sidebar down until the top of the sidebar is reached.
	if ( stickySidebarIsTallerThanViewport && ! initialPosition ) {
		stickySidebarNewTop = stickySidebarRect.top - delta;
		const stickySidebarNewBottom = stickySidebarRect.bottom - delta;
		const stickySidebarMinTop = sidebarRect.top;
		const stickySidebarMinBottom =
			window.innerHeight - SIDEBAR_BOTTOM_MARGIN;

		// The sidebar is moving upwards.
		if ( delta >= 0 ) {
			if ( stickySidebarNewBottom < stickySidebarMinBottom ) {
				stickySidebarNewTop =
					stickySidebarMinBottom - stickySidebarHeight;
			}

			// The sidebar is moving downwards.
		} else {
			if ( stickySidebarNewTop > headerRect.bottom ) {
				stickySidebarNewTop = headerRect.bottom;
			}
			if ( stickySidebarNewTop < stickySidebarMinTop ) {
				stickySidebarNewTop = stickySidebarMinTop;
			}
		}

		// If the sidebar fits into the viewport vertically
		// then we simply stick it below the header when user
		// scrolls it up above the header.
	} else if ( sidebarRect.top <= headerRect.bottom ) {
		stickySidebarNewTop = headerRect.bottom;

		// By default we position the sticky sidebar on top
		// of the original sidebar.
	} else {
		stickySidebarNewTop = sidebarRect.top;
	}

	// Need to subtract the sidebar top margin because fixed positioned elements
	// are pushed down by css top margin.
	stickySidebar.style.top = `${ stickySidebarNewTop - sidebarMarginTop }px`;
}

/**
 * Reinitializes the sticky sideber
 */
const reinitializeSidebar = debounce( () => {
	preparestickySidebar();
	updateSidebarPosition( true );
}, 500 );

/**
 * Makes sure the height of the sidebar is at least the height
 * of the featured video in 'modern' LM template.
 */
function syncSidebarSizeWithVideo() {
	if ( featuredVideo && sidebar ) {
		new window.ResizeObserver( () => {
			const videoHeight = featuredVideo.offsetHeight;
			const sidebarHeight = sidebar.offsetHeight;
			if (
				! videoHeight ||
				! sidebarHeight ||
				sidebarHeight >= videoHeight
			) {
				return;
			}
			sidebar.style.height = `${ videoHeight }px`;
			reinitializeSidebar();
		} ).observe( featuredVideo );
	}
}

/**
 * Makes the sidebar sticky for relevant LM templates.
 */
function setupStickySidebar() {
	if ( ! isStickySidebar() ) {
		return;
	}

	queryDomElements();

	document.defaultView.addEventListener( 'scroll', () =>
		updateSidebarPosition()
	);

	// eslint-disable-next-line @wordpress/no-global-event-listener
	window.addEventListener( 'resize', reinitializeSidebar );

	// Make sure sidebar height is not shorter than the video height
	// for `moderm` lm template.
	if ( document.body.classList.contains( 'learning-mode--modern' ) ) {
		syncSidebarSizeWithVideo();
	}

	reinitializeSidebar();
}

// eslint-disable-next-line @wordpress/no-global-event-listener
window.addEventListener( 'DOMContentLoaded', setupStickySidebar );
