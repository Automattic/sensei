/**
 * Internal dependencies
 */
import { toggleFocusMode } from './focus-mode';
import './contact-teacher';

if ( ! window.sensei ) {
	window.sensei = {};
}

window.sensei.courseTheme = { toggleFocusMode };
