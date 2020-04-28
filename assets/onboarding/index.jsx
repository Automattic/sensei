import { render } from '@wordpress/element';
import { useFullScreen } from '../react-hooks';
import { steps } from './steps.jsx';
import { QueryStringRouter, Route } from './query-string-router';
import Navigation from './navigation';

/**
 * Param name used to route the onboarding wizard.
 */
const PARAM_NAME = 'step';

const SenseiOnboardingPage = () => {
	useFullScreen( [ 'sensei-color', 'sensei-onboarding__page' ] );

	return (
		<QueryStringRouter paramName={ PARAM_NAME }>
			<div className="sensei-onboarding__header">
				<Navigation steps={ steps } />
			</div>
			<div className="sensei-onboarding__container">
				{ steps.map( ( step, i ) =>
					<Route
						key={ step.key }
						route={ step.key }
						defaultRoute={ 0 === i }
					>
						{ step.container }
					</Route>
				) }
			</div>
		</QueryStringRouter>
	);
};

render(
	<SenseiOnboardingPage />,
	document.getElementById( 'sensei-onboarding-page' )
);
