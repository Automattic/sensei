import { useState } from '@wordpress/element';
import { Card, H } from '@woocommerce/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

import { INSTALLED_STATUS } from './feature-status';
import { useQueryStringRouter } from '../query-string-router';
import ConfirmationModal from './confirmation-modal';
import InstallationFeedback from './installation-feedback';
import FeaturesSelection from './features-selection';

const addPriceToTitle = ( features ) =>
	features.map( ( feature ) => {
		let titleComplement;

		if ( feature.status === INSTALLED_STATUS ) {
			titleComplement = __( 'Installed', 'sensei-lms' );
		} else {
			titleComplement = feature.price
				? `${ feature.price } ${ __( 'per year', 'sensei-lms' ) }`
				: __( 'Free', 'sensei-lms' );
		}

		return {
			...feature,
			title: `${ feature.title } — ${ titleComplement }`,
		};
	} );

/**
 * Features step for setup wizard.
 */
const Features = () => {
	const [ confirmationActive, toggleConfirmation ] = useState( false );
	const [ feedbackActive, toggleFeedback ] = useState( false );
	const [ selectedFeatureIds, setSelectedFeatureIds ] = useState( [] );
	const { goTo } = useQueryStringRouter();

	const { features } = useSelect(
		( select ) => ( {
			features: addPriceToTitle(
				select( 'sensei/setup-wizard' ).getStepData( 'features' )
			),
		} ),
		[]
	);

	const getSelectedFeatures = () =>
		features.filter( ( feature ) =>
			selectedFeatureIds.includes( feature.id )
		);

	const finishSelection = () => {
		if ( 0 === selectedFeatureIds.length ) {
			goToNextStep();
			return;
		}

		toggleConfirmation( true );
	};

	const goToInstallation = () => {
		toggleConfirmation( false );
		toggleFeedback( true );
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
						selectedIds={ selectedFeatureIds }
						onChange={ setSelectedFeatureIds }
						onContinue={ finishSelection }
					/>
				) }
			</Card>

			{ confirmationActive && (
				<ConfirmationModal
					features={ getSelectedFeatures() }
					onInstall={ goToInstallation }
					onSkip={ goToNextStep }
				/>
			) }
		</>
	);
};

export default Features;
