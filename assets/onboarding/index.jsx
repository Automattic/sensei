import { render } from '@wordpress/element';
import { useFullScreen } from '../react-hooks';
import { steps } from './steps.jsx';
import { QueryStringRouter } from './query-string-router';
import Navigation from './navigation';
import ContentContainer from './content-container';

/**
 * Query string name used to route the onboarding wizard.
 */
const QUERY_STRING_NAME = 'onboarding-step';

const SenseiOnboardingPage = () => {
	useFullScreen( [ 'sensei-color', 'sensei-onboarding__page' ] );

	return (
		<QueryStringRouter
			routes={ steps }
			queryStringName={ QUERY_STRING_NAME }
		>
			<Navigation routes={ steps } />
			<ContentContainer />
		</QueryStringRouter>
	);
};

render(
	<SenseiOnboardingPage />,
	document.getElementById( 'sensei-onboarding-page' )
);
