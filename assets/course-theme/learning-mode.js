/**
 * Internal dependencies
 */
import './adminbar-layout';
import './featured-video-size';
import './scroll-direction';
import { initCompleteLessonTransition } from './complete-lesson-button';
import { submitContactTeacher } from './contact-teacher';
import { toggleFocusMode } from './focus-mode';

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

initCompleteLessonTransition();
