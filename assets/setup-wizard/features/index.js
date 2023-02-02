/**
 * WordPress dependencies
 */
import { useMemo } from '@wordpress/element';
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { useSetupWizardStep } from '../data/use-setup-wizard-step';
import useActionsNavigator, {
	actionMinimumTimer,
} from './use-actions-navigator';
import { HOME_PATH } from '../constants';

const featureLabels = {
	woocommerce: __( 'Installing WooCommerce', 'sensei-lms' ),
	'sensei-certificates': __( 'Installing Certificates', 'sensei-lms' ),
};

/**
 * Get actions for the features to be installed.
 *
 * @param {Object}   featuresData          The features step data.
 * @param {string[]} featuresData.selected Selected features to be installed.
 * @param {Object[]} featuresData.options  Features available to install.
 *
 * @return {Array} Actions to install the selected features.
 */
const getFeatureActions = ( { selected, options } ) => {
	// Filter not activated features.
	const featuresToInstall = selected.filter( ( slug ) =>
		options.some(
			( option ) => option.product_slug === slug && ! option.is_activated
		)
	);

	return featuresToInstall.map( ( slug ) => ( {
		label: featureLabels[ slug ],
		action: () =>
			apiFetch( {
				path: '/sensei-internal/v1/sensei-extensions/install',
				method: 'POST',
				data: {
					plugin: slug,
				},
			} ),
	} ) );
};

/**
 * Get the action for the theme installation.
 *
 * @return {Object} Action to install the Sensei theme.
 */
const getThemeAction = () => ( {
	label: __( 'Installing the Course theme', 'sensei-lms' ),
	action: () =>
		apiFetch( {
			path: '/sensei-internal/v1/themes/install',
			method: 'POST',
			data: {
				theme: 'course',
			},
		} ),
} );

/**
 * Features step for Setup Wizard.
 */
const Features = () => {
	const {
		stepData: featuresData,
		submitStep,
		error: submitError,
	} = useSetupWizardStep( 'features' );
	const { stepData: themeData } = useSetupWizardStep( 'theme' );
	const { install_sensei_theme: installSenseiTheme } = themeData;

	// Create list of actions to install.
	const installActions = useMemo( () => {
		const list = getFeatureActions( featuresData );

		if ( installSenseiTheme ) {
			list.push( getThemeAction() );
		}

		return list;
	}, [ featuresData, installSenseiTheme ] );

	// Create final list of actions.
	const actions = useMemo(
		() => [
			{
				label: __( 'Applying your choices', 'sensei-lms' ),
			},
			...installActions,
			{
				label: __( 'Setting up your new Sensei Home', 'sensei-lms' ),
				action: () => {
					let timeoutId;

					const action = new Promise( ( resolve ) => {
						timeoutId = setTimeout( () => {
							submitStep(
								{},
								{
									onSuccess: () => {
										window.location.href = HOME_PATH;
										resolve();
									},
								}
							);
						}, actionMinimumTimer );
					} );

					action.clearAction = () => clearTimeout( timeoutId );

					return action;
				},
			},
		],
		[ installActions, submitStep ]
	);

	const {
		percentage,
		label,
		error: actionError,
		errorActions,
	} = useActionsNavigator( actions );

	const error = actionError || submitError;

	return (
		<div className="sensei-setup-wizard__full-centered-step">
			<div className="sensei-setup-wizard__full-centered-content">
				<div
					className="sensei-setup-wizard__features-status"
					role="status"
					aria-live="polite"
				>
					<div className="sensei-setup-wizard__fade-in" key={ label }>
						{ label }
					</div>
				</div>

				{ error && (
					<Notice
						status="error"
						className="sensei-setup-wizard__error-notice"
						isDismissible={ false }
						actions={
							errorActions || [
								{
									label: __(
										'Go to Sensei Home',
										'sensei-lms'
									),
									url: HOME_PATH,
								},
							]
						}
					>
						{ error.message }
					</Notice>
				) }

				<div className="sensei-setup-wizard__features-progress-bar">
					<div
						role="progressbar"
						aria-label={ __(
							'Sensei Onboarding Progress',
							'sensei-lms'
						) }
						aria-valuenow={ percentage }
						className="sensei-setup-wizard__features-progress-bar-filled"
						style={ { width: `${ percentage }%` } }
					/>
				</div>
			</div>
		</div>
	);
};

export default Features;
