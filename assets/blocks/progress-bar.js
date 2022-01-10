import { render } from '@wordpress/element';
import ProgressBar from '../shared/blocks/progress-bar';
const barAttributes = {
	style: {
		...( window.php_vars.color && {
			backgroundColor: window.php_vars.color,
		} ),
		...( window.php_vars.radius && {
			borderRadius: parseInt( window.php_vars.radius ),
		} ),
	},
};

const barWrapperAttributes = {
	style: {
		...( window.php_vars.backgroundColor && {
			backgroundColor: window.php_vars.backgroundColor,
		} ),
		...( window.php_vars.height && {
			height: parseInt( window.php_vars.height ),
		} ),
		...( window.php_vars.radius && {
			borderRadius: parseInt( window.php_vars.radius ),
		} ),
	},
};

const wrapperAttributes = {
	className: 'sensei-block-wrapper',
};
render(
	<ProgressBar
		lessonsCount={ window.php_vars.totalNumber }
		completedCount={ window.php_vars.completedNumber }
		hidePercentage={ false }
		barAttributes={ barAttributes }
		barWrapperAttributes={ barWrapperAttributes }
		wrapperAttributes={ wrapperAttributes }
	/>,
	document.getElementById( 'progress-bar-quiz' )
);
