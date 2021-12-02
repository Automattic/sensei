/**
 * External dependencies
 */
import ReactDOM from 'react-dom';
import ReactModal from 'react-modal';

/**
 * Internal dependencies
 */
import { ContactTeacherBlocks } from './ContactTeacherBlocks';

window.onload = function () {
	const element = document.createElement( 'div' );
	const parent =
		document.getElementsByClassName(
			'sensei-course-theme__frame'
		)?.[ 0 ] || document.body;
	parent.appendChild( element );
	ReactModal.setAppElement( element );
	ReactDOM.render( <ContactTeacherBlocks />, element );
};
