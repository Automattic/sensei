import { render } from '@wordpress/element';
import ProgressBar from '../shared/blocks/progress-bar';
const barAttributes = {
	// className: barColor?.class || defaultBarColor?.className,
	style: {
		...( window.php_vars.backgroundColor && {
			backgroundColor: window.php_vars.backgroundColor,
		} ),
		...( window.php_vars.radius && {
			borderRadius: parseInt( window.php_vars.radius ),
		} ),
	},
};
render(
	<ProgressBar
		lessonsCount={ window.php_vars.totalNumber }
		completedCount={ window.php_vars.completedNumber }
		wrapperAttributes={ {
			className: 'wp-block-sensei-lms-progress-bar',
		} }
		hidePercentage={ false }
		barAttributes={ barAttributes }
	/>,
	document.getElementById( 'progress-bar-quiz' )
);
