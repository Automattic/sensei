/**
 * WordPress dependencies
 */
import { Button, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { logLink } from '../../shared/helpers/log-event';
import { useSetupWizardStep } from '../data/use-setup-wizard-step';
import List from '../../shared/components/list';

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
			className="sensei-setup-wizard__mailinglist-signup-form"
		>
			<input
				type="hidden"
				name={ `gdpr[${ stepData.gdpr_field }]` }
				value="Y"
			/>
			<List
				className="sensei-setup-wizard__item-list"
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
								className="sensei-setup-wizard__button"
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
