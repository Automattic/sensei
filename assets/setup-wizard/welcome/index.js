import { Card, H, Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { UsageModal } from './usage-modal';
import { useQueryStringRouter } from '../query-string-router';
import { useSetupWizardStep } from '../data/use-setup-wizard-step';

/**
 * Welcome step for Setup Wizard.
 */
export const Welcome = () => {
	const [ usageModalActive, toggleUsageModal ] = useState( false );

	const { goTo } = useQueryStringRouter();

	const {
		stepData,
		submitStep,
		isSubmitting,
		errorNotice,
	} = useSetupWizardStep( 'welcome' );

	const onSubmitSuccess = () => {
		toggleUsageModal( false );
		goTo( 'purpose' );
	};

	const submitPage = ( allowUsageTracking ) => {
		submitStep(
			{ usage_tracking: allowUsageTracking },
			{ onSuccess: onSubmitSuccess }
		);
	};

	return (
		<>
			<div className="sensei-setup-wizard__title">
				<H> { __( 'Welcome to Sensei LMS!', 'sensei-lms' ) } </H>
			</div>
			<Card className="sensei-setup-wizard__card">
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
					className="sensei-setup-wizard__button sensei-setup-wizard__button-card"
					onClick={ () => toggleUsageModal( true ) }
				>
					{ __( 'Continue', 'sensei-lms' ) }
				</Button>
			</Card>
			<div className="sensei-setup-wizard__bottom-actions">
				<Link
					href="edit.php?post_type=course"
					type="wp-admin"
					className="link__color-secondary"
				>
					{ __( 'Not right now', 'sensei-lms' ) }
				</Link>
			</div>
			{ usageModalActive && (
				<UsageModal
					tracking={ stepData.usage_tracking }
					isSubmitting={ isSubmitting }
					onClose={ () => toggleUsageModal( false ) }
					onContinue={ submitPage }
				>
					{ errorNotice }
				</UsageModal>
			) }
		</>
	);
};
