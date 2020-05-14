import { useSelect, useDispatch } from '@wordpress/data';
import { Card, H, Link } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { UsageModal } from './usage-modal';
import { useQueryStringRouter } from '../query-string-router';

/**
 * Welcome step for Onboarding Wizard.
 */
export const Welcome = () => {
	const [ usageModalActive, toggleUsageModal ] = useState( false );

	const { goTo } = useQueryStringRouter();

	const { usageTracking, isFetching, isSubmitting } = useSelect(
		( select ) => ( {
			usageTracking: select( 'sensei-setup-wizard' ).getUsageTracking(),
			isFetching: select(
				'sensei-setup-wizard'
			).isFetchingUsageTracking(),
			isSubmitting: select(
				'sensei-setup-wizard'
			).isSubmittingUsageTracking(),
		} ),
		[]
	);
	const { submitUsageTracking } = useDispatch( 'sensei-setup-wizard' );

	const isBusy = isFetching || isSubmitting;

	async function submitPage( allowUsageTracking ) {
		await submitUsageTracking( allowUsageTracking );
		toggleUsageModal( false );
		goTo( 'purpose' );
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
			<div className="sensei-onboarding__skip">
				<Link href="edit.php?post_type=course" type="wp-admin">
					{ __( 'Not right now', 'sensei-lms' ) }
				</Link>
			</div>
			{ usageModalActive && (
				<UsageModal
					tracking={ usageTracking }
					isSubmitting={ isBusy }
					onClose={ () => toggleUsageModal( false ) }
					onContinue={ submitPage }
				/>
			) }
		</>
	);
};
