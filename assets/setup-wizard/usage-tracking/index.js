/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useQueryStringRouter } from '../../shared/query-string-router';
import { useSetupWizardStep } from '../data/use-setup-wizard-step';
import { H } from '../../shared/components/section';

/**
 * Usage Tracking step for Setup Wizard.
 */
const UsageTracking = () => {
	const { goTo } = useQueryStringRouter();

	const { submitStep, isSubmitting, errorNotice } = useSetupWizardStep(
		'tracking'
	);

	const onSubmitSuccess = () => {
		goTo( 'features' );
	};

	const submitPage = ( allowUsageTracking ) => () => {
		submitStep(
			{ usage_tracking: allowUsageTracking },
			{ onSuccess: onSubmitSuccess }
		);
	};

	return (
		<div className="sensei-setup-wizard__welcome-step">
			<H className="sensei-setup-wizard__step-title">
				{ __( 'Help us improve your Sensei experience', 'sensei-lms' ) }
			</H>
			<p>
				{ __(
					'Help us build a better Sensei by sharing anonymous and non-sensitive data with our team. No personal data is tracked or stored, and this helps us track down bugs and plan future improvements.',
					'sensei-lms'
				) }
			</p>
			<div className="sensei-setup-wizard__actions">
				{ errorNotice }
				<button
					disabled={ isSubmitting }
					className="sensei-setup-wizard__button sensei-setup-wizard__button--primary"
					onClick={ submitPage( true ) }
				>
					{ __( 'Exciting, count me in!', 'sensei-lms' ) }
				</button>
				<div className="sensei-setup-wizard__action-skip">
					<button
						disabled={ isSubmitting }
						className="sensei-setup-wizard__button sensei-setup-wizard__button--link"
						onClick={ submitPage( false ) }
					>
						{ __( 'Skip sharing data', 'sensei-lms' ) }
					</button>
				</div>
			</div>
		</div>
	);
};

export default UsageTracking;
