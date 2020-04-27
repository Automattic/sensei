import { Card, H } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { UsageModal } from './usage-modal.jsx';
import { usePageApi } from './use-page-api.jsx';

export function Welcome() {
	const [ usageModalActive, toggleUsageModal ] = useState( false );

	const [ data, submit ] = usePageApi( 'welcome' );

	function submitPage( allowUsageTracking ) {
		toggleUsageModal( false );
		submit( { usage_tracking: allowUsageTracking } );
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
				<Button isLink>{ __( 'Not right now', 'sensei-lms' ) }</Button>
			</div>
			{ usageModalActive && (
				<UsageModal
					tracking={ data.usage_tracking }
					onClose={ () => toggleUsageModal( false ) }
					onContinue={ submitPage }
				/>
			) }
		</>
	);
}
