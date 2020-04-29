import { Card, H, Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { UsageModal } from './usage-modal.jsx';
import { useOnboardingApi } from './use-onboarding-api.jsx';

/**
 * Welcome step for Onboarding Wizard.
 *
 * @class
 */
export function Welcome() {
	const [ usageModalActive, toggleUsageModal ] = useState( false );

	const { data, submit, isBusy } = useOnboardingApi( 'welcome' );

	async function submitPage( allowUsageTracking ) {
		await submit( { usage_tracking: allowUsageTracking } );
		toggleUsageModal( false );
	}

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
						'This setup wizard will help you get started creating online courses more quickly. It is optional and should only take a few minutes.',
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
			<div className="sensei-onboarding__skip">
				<Link href="edit.php?post_type=course" type="wp-admin">
					{ __( 'Not right now', 'sensei-lms' ) }
				</Link>
			</div>
			{ usageModalActive && (
				<UsageModal
					tracking={ data.usage_tracking }
					isSubmitting={ isBusy }
					onClose={ () => toggleUsageModal( false ) }
					onContinue={ submitPage }
				/>
			) }
		</>
	);
}
