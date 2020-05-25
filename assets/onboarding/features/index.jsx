import { useState, useEffect } from '@wordpress/element';
import { Card, H } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { uniq } from 'lodash';

import { useQueryStringRouter } from '../query-string-router';
import ConfirmationModal from './confirmation-modal';
import InstallationFeedback from './installation-feedback';
import FeaturesSelection from './features-selection';
import { useSetupWizardStep } from '../data/use-setup-wizard-step';

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

	// Mark as selected also the already submitted slugs.
	useEffect( () => {
		setSelectedSlugs( ( currentSelected ) =>
			uniq( [ ...currentSelected, ...submittedSlugs ] )
		);
	}, [ submittedSlugs ] );

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
