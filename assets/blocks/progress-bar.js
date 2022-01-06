import { render } from '@wordpress/element';
import ProgressBar from '../shared/blocks/progress-bar';

render(
	<ProgressBar
		lessonsCount={ window.php_vars.totalNumber }
		completedCount={ window.php_vars.completedNumber }
		wrapperAttributes={ {
			className: 'wp-block-sensei-lms-progress-bar',
		} }
		hidePercentage={ false }
	/>,
	document.getElementById( 'progress-bar-quiz' )
);
