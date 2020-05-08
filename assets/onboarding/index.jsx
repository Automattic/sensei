import { render } from '@wordpress/element';
import { useWpAdminFullscreen } from '../react-hooks';
import { steps } from './steps';
import QueryStringRouter, { Route } from './query-string-router';
import Navigation from './navigation';

/**
 * Param name used to route the setup wizard.
 */
const PARAM_NAME = 'step';

/**
 * Sensei setup wizard page.
 */
const SenseiSetupWizardPage = () => {
	useWpAdminFullscreen( [ 'sensei-color', 'sensei-onboarding__page' ] );

	return (
		<QueryStringRouter paramName={ PARAM_NAME }>
			<div className="sensei-onboarding__header">
				<Navigation steps={ steps } />
			</div>
			<div className="sensei-onboarding__container">
				{ steps.map( ( step, i ) => (
					<Route
						key={ step.key }
						route={ step.key }
						defaultRoute={ 0 === i }
					>
						{ step.container }
					</Route>
				) ) }
			</div>
		</QueryStringRouter>
	);
};

render(
	<SenseiSetupWizardPage />,
	document.getElementById( 'sensei-onboarding-page' )
);
