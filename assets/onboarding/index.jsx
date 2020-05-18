import { useSelect, useDispatch } from '@wordpress/data';
import { render, useLayoutEffect } from '@wordpress/element';
import { Spinner } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

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

	const { isFetching, error } = useSelect(
		( select ) => ( {
			isFetching: select( 'sensei/setup-wizard' ).isFetching(),
			error: select( 'sensei/setup-wizard' ).getFetchError(),
		} ),
		[]
	);
	const { fetchSetupWizardData } = useDispatch( 'sensei/setup-wizard' );

	// We want to show the loading before any content.
	useLayoutEffect( () => {
		fetchSetupWizardData();
	}, [ fetchSetupWizardData ] );

	if ( isFetching ) {
		return <Spinner className="sensei-onboarding__main-loader" />;
	}

	if ( error ) {
		return __(
			'An error has occurred. Please try again later!',
			'sensei-lms'
		);
	}

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
