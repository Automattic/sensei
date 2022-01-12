/**
 * WordPress dependencies
 */
import { render } from '@wordpress/element';
/**
 * Internal dependencies
 */
import ProgressBar from '../shared/blocks/progress-bar';
const barAttributes = {
	style: {
		...( window.sensei_quiz_progress.color && {
			backgroundColor: window.sensei_quiz_progress.color,
		} ),
	},
};

const barWrapperAttributes = {
	style: {
		...( window.sensei_quiz_progress.backgroundColor && {
			backgroundColor: window.sensei_quiz_progress.backgroundColor,
		} ),
		...( window.sensei_quiz_progress.height && {
			height: parseInt( window.sensei_quiz_progress.height ),
		} ),
		...( window.sensei_quiz_progress.radius && {
			borderRadius: parseInt( window.sensei_quiz_progress.radius ),
		} ),
	},
};

const wrapperAttributes = {
	className: 'sensei-block-wrapper',
};
render(
	<ProgressBar
		lessonsCount={ window.sensei_quiz_progress.totalNumber }
		completedCount={ window.sensei_quiz_progress.completedNumber }
		hidePercentage={ false }
		barAttributes={ barAttributes }
		barWrapperAttributes={ barWrapperAttributes }
		wrapperAttributes={ wrapperAttributes }
		hideDefault={ true }
	/>,
	document.getElementById( 'progress-bar-quiz' )
);
