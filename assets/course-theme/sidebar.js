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
 * The clone of the sidebar DOM element.
 *
 * @member {HTMLElement}
 */
let sidebarClone = null;

/**
 * The featured video DOM element.
 *
 * @member {HTMLElement}
 */
let featuredVideo = null;

const queryDomElements = () => {
	sidebar = document.querySelector( '.sensei-course-theme__sidebar' );
	header = document.querySelector( '.sensei-course-theme__header' );
	featuredVideo = document.querySelector(
		'.sensei-course-theme-lesson-video'
	);
};

function prepareSidebarClone() {
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

const reinitializeSidebar = debounce( () => {
	prepareSidebarClone();
	updateSidebarPosition( true );
}, 500 );

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
function stickySidebar() {
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
window.addEventListener( 'DOMContentLoaded', stickySidebar );
