import { Card, H, Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { UsageModal } from './usage-modal';
import { useQueryStringRouter } from '../query-string-router';
import { useSetupWizardStep } from '../use-setup-wizard-step.js';

/**
 * Welcome step for Onboarding Wizard.
 */
export const Welcome = () => {
	const [ usageModalActive, toggleUsageModal ] = useState( false );
	const [ submittedData, toggleSubmittedData ] = useState( false );

	const { goTo } = useQueryStringRouter();

	const { stepData, submitStep, isSubmitting, error } = useSetupWizardStep(
		'welcome'
	);

	useEffect( () => {
		if ( submittedData && ! error ) {
			toggleUsageModal( false );
			goTo( 'purpose' );
		}

		// Clear in case of error.
		toggleSubmittedData( false );
	}, [ submittedData, error, goTo ] );

	const submitPage = async ( allowUsageTracking ) => {
		await submitStep( { usage_tracking: allowUsageTracking } );
		toggleSubmittedData( true );
	};

	return (
		<>
			<div className="sensei-onboarding__title">
				<H> { __( 'Welcome to Sensei LMS!', 'sensei-lms' ) } </H>
			</div>
			<Card className="sensei-onboarding__card">
				<p>
					{ __( 'Thank you for choosing Sensei LMS!', 'sensei-lms' ) }
				</p>
				<p>
					{ __(
						'This setup wizard will help you get started creating online courses more quickly. It is optional and should take only a few minutes.',
						'sensei-lms'
					) }
				</p>
				<Button
					isPrimary
					className="sensei-onboarding__button sensei-onboarding__button-card"
					onClick={ () => toggleUsageModal( true ) }
				>
					{ __( 'Continue', 'sensei-lms' ) }
				</Button>
			</Card>
			<div className="sensei-onboarding__bottom-actions">
				<Link href="edit.php?post_type=course" type="wp-admin">
					{ __( 'Not right now', 'sensei-lms' ) }
				</Link>
			</div>
			{ usageModalActive && (
				<UsageModal
					tracking={ stepData.usage_tracking }
					isSubmitting={ isSubmitting }
					onClose={ () => toggleUsageModal( false ) }
					onContinue={ submitPage }
				/>
			) }
		</>
	);
};
