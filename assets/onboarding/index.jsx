import { useSelect, useDispatch } from '@wordpress/data';
import { render, useLayoutEffect } from '@wordpress/element';
import { Spinner } from '@woocommerce/components';

import registerSetupWizardStore from './data';
import { useWpAdminFullscreen } from '../react-hooks';
import { steps } from './steps';
import QueryStringRouter, { Route } from './query-string-router';
import Navigation from './navigation';

/**
 * Register setup wizard store.
 */
registerSetupWizardStore();

/**
 * Param name used to route the setup wizard.
 */
const PARAM_NAME = 'step';

/**
 * Sensei setup wizard page.
 */
const SenseiSetupWizardPage = () => {
	useWpAdminFullscreen( [ 'sensei-color', 'sensei-onboarding__page' ] );

	const isFetching = useSelect(
		( select ) =>
			select( 'sensei/setup-wizard' ).isFetchingSetupWizardData(),
		[]
	);
	const { fetchSetupWizardData } = useDispatch( 'sensei/setup-wizard' );

	// We want to show the loading before any content.
	useLayoutEffect( () => {
		fetchSetupWizardData();
	}, [ fetchSetupWizardData ] );

	return isFetching ? (
		<Spinner className="sensei-onboarding__main-loader" />
	) : (
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
