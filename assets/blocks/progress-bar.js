import { render } from 'react-dom';
import ProgressBar from '../shared/blocks/progress-bar';

render(
	<ProgressBar
		lessonsCount={ 3 }
		completedCount={ 1 }
		wrapperAttributes={ {
			className: 'wp-block-sensei-lms-progress-bar',
		} }
		hidePercentage={ false }
	/>,
	document.getElementById( 'progress-bar-quiz' )
);
