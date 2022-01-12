/**
 * WordPress dependencies
 */
import { render } from '@wordpress/element';
import { _n } from '@wordpress/i18n';

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

const progressBarLabel = _n(
	'question',
	'questions',
	window.sensei_quiz_progress.completedNumber,
	'sensei-lms'
);
render(
	<ProgressBar
		totalCount={ window.sensei_quiz_progress.totalNumber }
		completedCount={ window.sensei_quiz_progress.completedNumber }
		hidePercentage={ false }
		barAttributes={ barAttributes }
		barWrapperAttributes={ barWrapperAttributes }
		wrapperAttributes={ wrapperAttributes }
		hideDefault={ true }
		progressBarLabel={ progressBarLabel }
	/>,
	document.getElementById( 'progress-bar-quiz' )
);
