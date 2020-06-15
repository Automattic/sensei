import { useState, useEffect, useCallback } from '@wordpress/element';
import { Card, H } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { uniq } from 'lodash';

import { INSTALLED_STATUS } from './feature-status';
import { logEvent } from '../log-event';
import { useQueryStringRouter } from '../query-string-router';
import {
	getParam,
	updateQueryString,
} from '../query-string-router/url-functions';
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
		setSelectedSlugs( ( prev ) =>
			uniq( [
				...prev,
				...filterInstalledFeatures( submittedSlugs, features ),
			] )
		);
	}, [ submittedSlugs, features ] );

	// Open directly in the feedback screen when there is a temporary feedback param.
	useEffect( () => {
		if ( getParam( 'feedback' ) ) {
			// Remove temporary param.
			updateQueryString( 'feedback', null, true );
			toggleFeedback( true );
		}
	}, [] );

	const getSelectedFeatures = useCallback(
		() =>
			features.filter( ( feature ) =>
				selectedSlugs.includes( feature.slug )
			),
		[ features, selectedSlugs ]
	);

	// Add or remove WooCommerce.
	useEffect( () => {
		const wcSlug = 'woocommerce';
		const selectedFeatures = getSelectedFeatures();
		const wooCommerceFeature = features.find( ( f ) => wcSlug === f.slug );
		const needWooCommerce = selectedFeatures.some(
			( feature ) => feature.wccom_product_id
		);
		const isWooCommerceSelected = selectedSlugs.includes( wcSlug );
		const isWooCommerceInstalled =
			wooCommerceFeature &&
			INSTALLED_STATUS === wooCommerceFeature.status;

		if (
			needWooCommerce &&
			! isWooCommerceSelected &&
			! isWooCommerceInstalled
		) {
			setSelectedSlugs( ( prev ) => [ wcSlug, ...prev ] );
		} else if ( ! needWooCommerce && isWooCommerceSelected ) {
			setSelectedSlugs( ( prev ) =>
				prev.filter( ( slug ) => slug !== wcSlug )
			);
		}
	}, [ getSelectedFeatures, features, selectedSlugs ] );

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

		logEvent( 'setup_wizard_features_install', {
			slug: selectedSlugs.join( ',' ),
		} );
	};

	const retryInstallation = ( selected ) => {
		submitInstallation( { selected } );

		logEvent( 'setup_wizard_features_install_retry', {
			slug: selected.join( ',' ),
		} );
	};

	const goToNextStep = ( skip = false ) => {
		goTo( 'ready' );

		const eventName =
			true === skip
				? 'setup_wizard_features_install_cancel'
				: 'setup_wizard_features_continue';

		logEvent( eventName, {
			slug: selectedSlugs.join( ',' ),
		} );
	};

	return (
		<>
			<div className="sensei-setup-wizard__title">
				<H>
					{ __(
						'Enhance your online courses with these optional features.',
						'sensei-lms'
					) }
				</H>
			</div>
			<Card className="sensei-setup-wizard__card">
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
					onSkip={ () => goToNextStep( true ) }
				/>
			) }
		</>
	);
};

export default Features;
