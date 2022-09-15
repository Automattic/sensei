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
 * Welcome step for Setup Wizard.
 */
const Welcome = () => {
	const { goTo } = useQueryStringRouter();

	const { submitStep, isSubmitting, errorNotice } = useSetupWizardStep(
		'welcome'
	);

	const onSubmitSuccess = () => {
		goTo( 'purpose' );
	};

	const submitPage = () => {
		submitStep( {}, { onSuccess: onSubmitSuccess } );
	};

	return (
		<div className="sensei-setup-wizard__welcome-step">
			<H className="sensei-setup-wizard__step-title">
				{ __( 'Welcome to Sensei LMS', 'sensei-lms' ) }
			</H>
			<p>
				{ __(
					"We'll have your first course live in no time.",
					'sensei-lms'
				) }
			</p>
			<div className="sensei-setup-wizard__actions">
				{ errorNotice }
				<button
					disabled={ isSubmitting }
					className="sensei-setup-wizard__button sensei-setup-wizard__button--primary"
					onClick={ submitPage }
				>
					{ __( 'Get started', 'sensei-lms' ) }
				</button>
				<div className="sensei-setup-wizard__action-skip">
					<a href="edit.php?post_type=course">
						{ __( 'Skip onboarding', 'sensei-lms' ) }
					</a>
				</div>
			</div>
		</div>
	);
};

export default Welcome;
