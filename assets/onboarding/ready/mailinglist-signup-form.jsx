import { List } from '@woocommerce/components';
import { Button, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { logLink } from '../log-event';
import { useSetupWizardStep } from '../data/use-setup-wizard-step';

/**
 * Sign up to Sensei Mailing list.
 *
 * Submits form to mailing list provider signup page in new tab.
 * Fills in site administrator e-mail address.
 */
export const MailingListSignupForm = () => {
	const { stepData } = useSetupWizardStep( 'ready' );

	return (
		<form
			action={ stepData.mc_url }
			method="post"
			target="_blank"
			className="sensei-onboarding__mailinglist-signup-form"
		>
			<input
				type="hidden"
				name={ `gdpr[${ stepData.gdpr_field }]` }
				value="Y"
			/>
			<List
				className="sensei-onboarding__item-list"
				items={ [
					{
						title: '',
						content: (
							<TextControl
								type="email"
								name="EMAIL"
								defaultValue={ stepData.admin_email }
							/>
						),
						after: (
							<Button
								className="sensei-onboarding__button"
								isPrimary
								type="submit"
								{ ...logLink(
									'setup_wizard_ready_mailing_list'
								) }
							>
								{ __( 'Yes, please!', 'sensei-lms' ) }
							</Button>
						),
					},
				] }
			/>
		</form>
	);
};
