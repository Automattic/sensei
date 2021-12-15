/**
 * Internal dependencies
 */
import './scroll-direction';
import './adminbar-layout';
import { toggleFocusMode } from './focus-mode';
import { submitContactTeacher } from './contact-teacher';

if ( ! window.sensei ) {
	window.sensei = {};
}

/**
 * Show or hide the sidebar in mobile mode.
 */
const toggleSidebar = () => {
	document.body.classList.toggle( 'sensei-course-theme--sidebar-open' );
};

window.sensei.courseTheme = { toggleFocusMode, toggleSidebar };
window.sensei.submitContactTeacher = submitContactTeacher;
