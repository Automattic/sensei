import { List, Spinner } from '@woocommerce/components';
import { Button, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useOnboardingApi } from '../use-onboarding-api.js';

/**
 * Sign up to Sensei Mailing list.
 *
 * Submits form to mailing list provider signup page in new tab.
 * Fills in site administrator e-mail address.
 */
export const MailingListSignupForm = () => {
	const { data, isBusy } = useOnboardingApi( 'ready' );

	if ( isBusy ) {
		return (
			<div style={ { textAlign: 'center' } }>
				<Spinner />
			</div>
		);
	}

	return (
		<form
			action={ data.mc_url }
			method="post"
			target="_blank"
			className="sensei-onboarding__mailinglist-signup-form"
		>
			<input
				type="hidden"
				name={ `gdpr[${ data.gdpr_field }]` }
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
								defaultValue={ data.admin_email }
							/>
						),
						after: (
							<Button isPrimary type="submit">
								{ __( 'Yes, please!', 'sensei-lms' ) }
							</Button>
						),
					},
				] }
			/>
		</form>
	);
};
