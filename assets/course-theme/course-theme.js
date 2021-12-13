/**
 * Internal dependencies
 */
import { toggleFocusMode } from './focus-mode';
import { submitContactTeacher } from './contact-teacher';

if ( ! window.sensei ) {
	window.sensei = {};
}

window.sensei.courseTheme = { toggleFocusMode };
window.sensei.submitContactTeacher = submitContactTeacher;
