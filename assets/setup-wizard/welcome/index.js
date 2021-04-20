/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Card, CardBody } from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { UsageModal } from './usage-modal';
import { useQueryStringRouter } from '../../shared/query-string-router';
import { useSetupWizardStep } from '../data/use-setup-wizard-step';
import { H } from '../../shared/components/section';

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
			<Card className="sensei-setup-wizard__card" isElevated={ true }>
				<CardBody>
					<p>
						{ __(
							'Thank you for choosing Sensei LMS!',
							'sensei-lms'
						) }
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
				</CardBody>
			</Card>
			<div className="sensei-setup-wizard__bottom-actions">
				<a
					href="edit.php?post_type=course"
					type="wp-admin"
					className="link__color-secondary"
				>
					{ __( 'Not right now', 'sensei-lms' ) }
				</a>
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
