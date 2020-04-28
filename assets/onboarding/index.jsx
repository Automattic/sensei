import { render } from '@wordpress/element';
import { useFullScreen } from '../react-hooks';
import { steps } from './steps.jsx';
import { QueryStringRouter, Route } from './query-string-router';
import Navigation from './navigation';

/**
 * Query string name used to route the onboarding wizard.
 */
const QUERY_STRING_NAME = 'onboarding-step';

const SenseiOnboardingPage = () => {
	useFullScreen( [ 'sensei-color', 'sensei-onboarding__page' ] );

	return (
		<QueryStringRouter queryStringName={ QUERY_STRING_NAME }>
			<div className="sensei-onboarding__header">
				<Navigation steps={ steps } />
			</div>
			<div className="sensei-onboarding__container">
				<Route route="welcome" defaultRoute>
					{ steps[0].container }
				</Route>
				<Route route="purpose">
					{ steps[1].container }
				</Route>
				<Route route="features">
					{ steps[2].container }
				</Route>
				<Route route="ready">
					{ steps[3].container }
				</Route>
			</div>
		</QueryStringRouter>
	);
};

render(
	<SenseiOnboardingPage />,
	document.getElementById( 'sensei-onboarding-page' )
);
