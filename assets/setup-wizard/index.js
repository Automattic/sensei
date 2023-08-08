/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { render, useLayoutEffect } from '@wordpress/element';
import { Notice, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import '../shared/data/api-fetch-preloaded-once';
import registerSetupWizardStore, { store as setupWizardStore } from './data';
import { useWpAdminFullscreen } from '../react-hooks';
import QueryStringRouter, {
	Route,
	useQueryStringRouter,
} from '../shared/query-string-router';
import NavigationBar from './navigation-bar';
import LogoTree from '../icons/logo-tree.svg';
import steps from './steps';

/**
 * Register setup wizard store.
 */
registerSetupWizardStore();

/**
 * Param name used to route the setup wizard.
 */
const PARAM_NAME = 'step';

/**
 * A component to set the full screen and a custom class related to the current route.
 */
const Fullscreen = () => {
	const { currentRoute } = useQueryStringRouter();

	useWpAdminFullscreen( [
		'sensei-setup-wizard-page',
		`sensei-setup-wizard-page--${ currentRoute }`,
	] );

	return null;
};

/**
 * Sensei setup wizard page.
 */
const SenseiSetupWizardPage = () => {
	const { isFetching, error } = useSelect( ( select ) => {
		const store = select( setupWizardStore );
		return {
			isFetching: store.isFetching(),
			error: store.getFetchError(),
		};
	}, [] );
	const { fetchSetupWizardData } = useDispatch( setupWizardStore );

	// We want to show the loading before any content.
	useLayoutEffect( () => {
		fetchSetupWizardData();
	}, [ fetchSetupWizardData ] );

	let content = null;

	if ( isFetching ) {
		content = <Spinner className="sensei-setup-wizard__main-loader" />;
	} else if ( error ) {
		content = (
			<Notice status="error" isDismissible={ false }>
				{ __(
					'An error has occurred while fetching the data. Please try again later!',
					'sensei-lms'
				) }
				<br />
				{ __( 'Error details:', 'sensei-lms' ) } { error.message }
			</Notice>
		);
	} else {
		content = (
			<div className="sensei-setup-wizard__container">
				{ steps.map( ( step ) => (
					<Route key={ step.key } route={ step.key }>
						{ step.container }
					</Route>
				) ) }
			</div>
		);
	}

	return (
		<QueryStringRouter
			paramName={ PARAM_NAME }
			defaultRoute={ steps[ 0 ].key }
		>
			<Fullscreen />

			<header className="sensei-setup-wizard__header">
				<NavigationBar steps={ steps } />

				<h1 className="sensei-setup-wizard__sensei-logo">
					<LogoTree /> Sensei
				</h1>
			</header>

			{ content }
		</QueryStringRouter>
	);
};

render(
	<SenseiSetupWizardPage />,
	document.getElementById( 'sensei-setup-wizard-page' )
);
