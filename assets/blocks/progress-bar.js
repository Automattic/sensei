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
		...( window.progress_bar_properties.color && {
			backgroundColor: window.progress_bar_properties.color,
		} ),
		...( window.progress_bar_properties.radius && {
			borderRadius: parseInt( window.progress_bar_properties.radius ),
		} ),
	},
};

const barWrapperAttributes = {
	style: {
		...( window.progress_bar_properties.backgroundColor && {
			backgroundColor: window.progress_bar_properties.backgroundColor,
		} ),
		...( window.progress_bar_properties.height && {
			height: parseInt( window.progress_bar_properties.height ),
		} ),
		...( window.progress_bar_properties.radius && {
			borderRadius: parseInt( window.progress_bar_properties.radius ),
		} ),
	},
};

const wrapperAttributes = {
	className: 'sensei-block-wrapper',
};
render(
	<ProgressBar
		lessonsCount={ window.progress_bar_properties.totalNumber }
		completedCount={ window.progress_bar_properties.completedNumber }
		hidePercentage={ false }
		barAttributes={ barAttributes }
		barWrapperAttributes={ barWrapperAttributes }
		wrapperAttributes={ wrapperAttributes }
		hideDefault={ true }
	/>,
	document.getElementById( 'progress-bar-quiz' )
);
