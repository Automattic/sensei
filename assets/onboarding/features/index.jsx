import { useState } from '@wordpress/element';
import { Card, H } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
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

	const {
		stepData,
		submitStep,
		isSubmitting,
		errorNotice,
	} = useSetupWizardStep( 'features' );
	const features = stepData.options;

	const getSelectedFeatures = () =>
		features.filter( ( feature ) =>
			selectedSlugs.includes( feature.slug )
		);

	const finishSelection = () => {
		if ( 0 === selectedSlugs.length ) {
			goToNextStep();
			return;
		}

		toggleConfirmation( true );
	};

	const onSubmitSuccess = () => {
		toggleConfirmation( false );
		toggleFeedback( true );
	};

	const goToInstallation = () => {
		submitStep(
			{ selected: selectedSlugs },
			{ onSuccess: onSubmitSuccess }
		);
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
						features={ getSelectedFeatures() }
						onContinue={ goToNextStep }
					/>
				) : (
					<FeaturesSelection
						features={ features }
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
					onInstall={ goToInstallation }
					onSkip={ goToNextStep }
				/>
			) }
		</>
	);
};

export default Features;
