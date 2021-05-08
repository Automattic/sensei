/**
 * External dependencies
 */
import { uniq } from 'lodash';

/**
 * WordPress dependencies
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Card, CardBody } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { INSTALLED_STATUS } from './feature-status';
import { logEvent } from '../../shared/helpers/log-event';
import { useQueryStringRouter } from '../../shared/query-string-router';
import { useSetupWizardStep } from '../data/use-setup-wizard-step';
import {
	getWccomProductId,
	getWoocommerceComPurchaseUrl,
} from '../../shared/helpers/woocommerce-com';
import ConfirmationModal from './confirmation-modal';
import InstallationFeedback from './installation-feedback';
import FeaturesSelection from './features-selection';
import { H } from '../../shared/components/section';

/**
 * @typedef  {Object} Feature
 * @property {string} slug Feature slug.
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

const wcSlug = 'woocommerce';

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

	// Features installation data.
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

	// Get selected features based on the selectedSlugs.
	const getSelectedFeatures = useCallback(
		() =>
			features.filter( ( feature ) =>
				selectedSlugs.includes( feature.slug )
			),
		[ features, selectedSlugs ]
	);

	const isWooCommerceInstalled = useCallback( () => {
		const wooCommerceFeature = features.find( ( f ) => wcSlug === f.slug );
		return (
			wooCommerceFeature && INSTALLED_STATUS === wooCommerceFeature.status
		);
	}, [ features ] );

	// Add or remove WooCommerce to the selected slugs.
	useEffect( () => {
		const selectedFeatures = getSelectedFeatures();
		const isWooCommerceSelected = selectedFeatures.some(
			( feature ) => feature.slug === wcSlug
		);
		const needWooCommerce = selectedFeatures.some( getWccomProductId );

		if ( ! needWooCommerce && isWooCommerceSelected ) {
			setSelectedSlugs( ( prev ) =>
				prev.filter( ( slug ) => slug !== wcSlug )
			);
			return;
		}

		if (
			needWooCommerce &&
			! isWooCommerceSelected &&
			! isWooCommerceInstalled()
		) {
			setSelectedSlugs( ( prev ) => [ ...prev, wcSlug ] );
		}
	}, [ getSelectedFeatures, isWooCommerceInstalled ] );

	// Finish and submit features selection.
	const finishSelection = () => {
		submitStep(
			{ selected: selectedSlugs },
			{
				onSuccess: () => {
					toggleConfirmation( true );
					if ( 0 === selectedSlugs.length ) {
						goToNextStep();
					}
				},
			}
		);
	};

	// Start features installation.
	const startInstallation = () => {
		logEvent( 'setup_wizard_features_install', {
			slug: selectedSlugs.join( ',' ),
		} );

		installFromWpOrg();
		installFromWooCommerce();
	};

	const installFromWpOrg = () => {
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

	const installFromWooCommerce = () => {
		const pendingWcFeatures = getSelectedFeatures().filter(
			( feature ) =>
				getWccomProductId( feature ) &&
				INSTALLED_STATUS !== feature.status
		);
		if ( ! pendingWcFeatures.length ) return;
		const wcPurchaseUrl = getWoocommerceComPurchaseUrl(
			pendingWcFeatures,
			stepData.wccom
		);
		window.open( wcPurchaseUrl );
	};

	// Retry features installation.
	const retryInstallation = ( selected ) => {
		submitInstallation( { selected } );

		logEvent( 'setup_wizard_features_install_retry', {
			slug: selected.join( ',' ),
		} );
	};

	// Go to the next step.
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
			<Card className="sensei-setup-wizard__card" isElevated={ true }>
				<CardBody>
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
				</CardBody>
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
