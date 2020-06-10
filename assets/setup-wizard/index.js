import { useSelect, useDispatch } from '@wordpress/data';
import { render, useLayoutEffect } from '@wordpress/element';
import { Spinner } from '@woocommerce/components';
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import registerSetupWizardStore from './data';
import { useWpAdminFullscreen } from '../react-hooks';

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
	useWpAdminFullscreen( [ 'sensei-color', 'sensei-setup-wizard__page' ] );

	const { isFetching, error, navigationSteps } = useSelect( ( select ) => {
		const store = select( 'sensei/setup-wizard' );
		return {
			isFetching: store.isFetching(),
			error: store.getFetchError(),
			navigationSteps: store.getNavigationSteps(),
		};
	}, [] );
	const { fetchSetupWizardData } = useDispatch( 'sensei/setup-wizard' );

	// We want to show the loading before any content.
	useLayoutEffect( () => {
		fetchSetupWizardData();
	}, [ fetchSetupWizardData ] );

	if ( isFetching ) {
		return <Spinner className="sensei-setup-wizard__main-loader" />;
	}

	if ( error ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ __(
					'An error has occurred while fetching the data. Please try again later!',
					'sensei-lms'
				) }
				<br />
				{ __( 'Error details:', 'sensei-lms' ) } { error.message }
			</Notice>
		);
	}

	return (
		<QueryStringRouter
			paramName={ PARAM_NAME }
			defaultRoute={ navigationSteps.find( ( step ) => step.isNext ).key }
		>
			<div className="sensei-setup-wizard__header">
				<Navigation steps={ navigationSteps } />
			</div>
			<div className="sensei-setup-wizard__container">
				{ navigationSteps.map( ( step ) => (
					<Route key={ step.key } route={ step.key }>
						{ step.container }
					</Route>
				) ) }
			</div>
		</QueryStringRouter>
	);
};

render(
	<SenseiSetupWizardPage />,
	document.getElementById( 'sensei-setup-wizard-page' )
);
