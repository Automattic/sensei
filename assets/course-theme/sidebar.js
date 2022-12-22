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
	!! document.querySelectorAll( '.sensei-course-theme__sidebar--is-sticky' )
		.length;

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
 * A placeholder for the sidebar.
 *
 * @member {HTMLElement}
 */
let sidebarPlaceholder = null;

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
 * Sets 'position: fixed' for the sidebar and puts a placeholder in it's original
 * place so the original layout is preserved. We also use the placeholder for sticky
 * sidebar position calculation to determine where to put it in any given time.
 */
function preparestickySidebar() {
	if ( ! sidebarPlaceholder ) {
		sidebarPlaceholder = sidebar.cloneNode();
		sidebarPlaceholder.style.visibility = 'hidden';
		sidebarPlaceholder.setAttribute( 'aria-hidden', 'true' );
		sidebar.style.transition = 'none';
		sidebar.style.position = 'fixed';
		sidebar.style.marginTop = '0';
		sidebar.parentElement.prepend( sidebarPlaceholder );
	}
	const sidebarRect = sidebarPlaceholder.getBoundingClientRect();
	sidebar.style.top = `0`;
	sidebar.style.left = `${ sidebarRect.left }px`;
	sidebar.style.width = `${ sidebarRect.right - sidebarRect.left }px`;
	sidebar.style.transform = `translateY(${ sidebarRect.top }px)`;
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
	if ( ! sidebar ) {
		return;
	}

	// Get the current dimensions of the elements.
	const headerRect = header.getBoundingClientRect();
	const sidebarPlaceholderRect = sidebarPlaceholder.getBoundingClientRect();
	const sidebarRect = sidebar.getBoundingClientRect();

	// Calculate required values.
	const delta = getScrollDelta();
	const sidebarHeight = sidebarRect.bottom - sidebarRect.top;
	const sidebarIsTallerThanViewport =
		sidebarHeight >
		window.innerHeight - ( headerRect.bottom + SIDEBAR_BOTTOM_MARGIN );
	let sidebarNewTop = sidebarPlaceholderRect.top;

	// If the sidebar is very tall and does not fit into the viewport vertically
	// we scroll the sticky sidebar up until the bottom is reached. Or we scroll
	// the sticky sidebar down until the top of the sidebar is reached.
	if ( sidebarIsTallerThanViewport && ! initialPosition ) {
		sidebarNewTop = sidebarRect.top - delta;
		const sidebarNewBottom = sidebarRect.bottom - delta;
		const sidebarMinTop = sidebarPlaceholderRect.top;
		const sidebarMinBottom = window.innerHeight - SIDEBAR_BOTTOM_MARGIN;

		// The sidebar is moving upwards.
		if ( delta >= 0 ) {
			if ( sidebarNewBottom < sidebarMinBottom ) {
				sidebarNewTop = sidebarMinBottom - sidebarHeight;
			}

			// The sidebar is moving downwards.
		} else {
			if ( sidebarNewTop > headerRect.bottom ) {
				sidebarNewTop = headerRect.bottom;
			}
			if ( sidebarNewTop < sidebarMinTop ) {
				sidebarNewTop = sidebarMinTop;
			}
		}

		// If the sidebar fits into the viewport vertically
		// then we simply stick it below the header when user
		// scrolls it up above the header.
	} else if ( sidebarPlaceholderRect.top <= headerRect.bottom ) {
		sidebarNewTop = headerRect.bottom;

		// By default we position the sticky sidebar on top
		// of the original sidebar.
	} else {
		sidebarNewTop = sidebarPlaceholderRect.top;
	}

	// Need to subtract the sidebar top margin because fixed positioned elements
	// are pushed down by css top margin.

	sidebar.style.transform = `translateY(${ sidebarNewTop }px)`;
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
