/**
 * WordPress dependencies
 */
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { logLink } from '../../shared/helpers/log-event';
import { useSetupWizardStep } from '../data/use-setup-wizard-step';

/**
 * Sign up to Sensei Mailing list.
 *
 * Submits form to mailing list provider signup page in new tab.
 * Fills in site administrator e-mail address.
 *
 * @param {Object}   props          Component props.
 * @param {Function} props.onSubmit Submit callback.
 */
const SignupForm = ( { onSubmit } ) => {
	const { stepData } = useSetupWizardStep( 'newsletter' );

	const submitHandler = () => {
		// Make sure it will run after the submit is done.
		setTimeout( () => {
			onSubmit();
		} );
	};

	return (
		<form
			action={ stepData.mc_url }
			method="post"
			target="_blank"
			className="sensei-setup-wizard__signup-form"
			onSubmit={ submitHandler }
		>
			<input
				type="hidden"
				name={ `gdpr[${ stepData.gdpr_field }]` }
				value="Y"
			/>

			<TextControl
				label={ __( 'Your email address', 'sensei-lms' ) }
				type="email"
				name="EMAIL"
				defaultValue={ stepData.admin_email }
			/>

			<button
				className="sensei-setup-wizard__button sensei-setup-wizard__button--primary"
				type="submit"
				{ ...logLink( 'setup_wizard_newsletter_signup' ) }
			>
				{ __( 'Nice! Sign me up', 'sensei-lms' ) }
			</button>
		</form>
	);
};

export default SignupForm;
