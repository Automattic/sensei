import { useState, useEffect } from '@wordpress/element';
import { Card, H } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { uniq } from 'lodash';

import { INSTALLED_STATUS } from './feature-status';
import { logEvent } from '../log-event';
import { useQueryStringRouter } from '../query-string-router';
import { useSetupWizardStep } from '../data/use-setup-wizard-step';
import ConfirmationModal from './confirmation-modal';
import InstallationFeedback from './installation-feedback';
import FeaturesSelection from './features-selection';

/**
 * @typedef  {Object} Feature
 * @property {string} slug    Feature slug.
 */
/**
 * Filter installed features to don't select them.
 *
 * @param {string[]}  submittedSlugs Submitted slugs.
 * @param {Feature[]} features       Features list.
 */
const filterInstalledFeatures = ( submittedSlugs, features ) =>
	submittedSlugs.filter( ( slug ) => {
		const feature = features.find( ( f ) => f.slug === slug );

		if ( ! feature ) {
			return false;
		}

		return INSTALLED_STATUS !== feature.status;
	} );

/**
 * Features step for setup wizard.
 */
const Features = () => {
	const [ confirmationActive, toggleConfirmation ] = useState( false );
	const [ feedbackActive, toggleFeedback ] = useState( false );
	const [ selectedSlugs, setSelectedSlugs ] = useState( [] );
	const { goTo } = useQueryStringRouter();

	// Features data.
	const {
		stepData,
		submitStep,
		isSubmitting,
		errorNotice,
	} = useSetupWizardStep( 'features' );
	const features = stepData.options;
	const submittedSlugs = stepData.selected;

	// Features installation.
	const { submitStep: submitInstallation } = useSetupWizardStep(
		'features-installation'
	);

	// Mark as selected also the already submitted slugs (Except the installed ones).
	useEffect( () => {
		setSelectedSlugs( ( currentSelected ) =>
			uniq( [
				...currentSelected,
				...filterInstalledFeatures( submittedSlugs, features ),
			] )
		);
	}, [ submittedSlugs, features ] );

	const getSelectedFeatures = () =>
		features.filter( ( feature ) =>
			selectedSlugs.includes( feature.slug )
		);

	const finishSelection = () => {
		if ( 0 === selectedSlugs.length ) {
			goToNextStep();
			return;
		}

		submitStep(
			{ selected: selectedSlugs },
			{ onSuccess: () => toggleConfirmation( true ) }
		);
	};

	const startInstallation = () => {
		submitInstallation(
			{ selected: selectedSlugs },
			{
				onSuccess: () => {
					toggleConfirmation( false );
					toggleFeedback( true );
				},
			}
		);
	};

	const retryInstallation = ( selected ) => {
		submitInstallation( { selected } );
	};

	const goToNextStep = () => {
		goTo( 'ready' );
		logEvent( 'setup_wizard_features_continue', { slug: selectedSlugs } );
	};

	return (
		<>
			<div className="sensei-onboarding__title">
				<H>
					{ __(
						'Enhance your online courses with these optional features.',
						'sensei-lms'
					) }
				</H>
			</div>
			<Card className="sensei-onboarding__card">
				{ feedbackActive ? (
					<InstallationFeedback
						onContinue={ goToNextStep }
						onRetry={ retryInstallation }
					/>
				) : (
					<FeaturesSelection
						features={ features }
						isSubmitting={ isSubmitting }
						errorNotice={ errorNotice }
						selectedSlugs={ selectedSlugs }
						onChange={ setSelectedSlugs }
						onContinue={ finishSelection }
					/>
				) }
			</Card>

			{ confirmationActive && (
				<ConfirmationModal
					features={ getSelectedFeatures() }
					isSubmitting={ isSubmitting }
					errorNotice={ errorNotice }
					onInstall={ startInstallation }
					onSkip={ goToNextStep }
				/>
			) }
		</>
	);
};

export default Features;
