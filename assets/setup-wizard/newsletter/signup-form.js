/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { TextControl, Notice } from '@wordpress/components';
import { addQueryArgs } from '@wordpress/url';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { logLink } from '../../shared/helpers/log-event';
import { useSetupWizardStep } from '../data/use-setup-wizard-step';

const SIGNUP_CALLBACK = 'senseiSignupCallback';

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

	const [ email, setEmail ] = useState( stepData.admin_email );
	const [ isSubmitting, setIsSubmitting ] = useState( false );
	const [ error, setError ] = useState( false );

	const submitHandler = ( e ) => {
		e.preventDefault();

		const formData = new window.FormData( e.target );

		const url = addQueryArgs(
			e.target.action,
			Object.fromEntries( formData )
		);

		// JSONP callback.
		window[ SIGNUP_CALLBACK ] = ( json ) => {
			setIsSubmitting( false );

			if ( 'error' === json.result ) {
				setError( json.msg );
			} else {
				onSubmit();
			}
		};

		// Create and append JSONP script.
		const script = document.createElement( 'script' );
		script.src = `${ url }&c=${ SIGNUP_CALLBACK }`;

		setIsSubmitting( true );
		setError( false );
		document.body.append( script );
	};

	return (
		<>
			{ error && (
				<Notice
					status="error"
					className="sensei-setup-wizard__error-notice"
					isDismissible={ false }
				>
					{ error }
				</Notice>
			) }
			<form
				action={ stepData.mc_url }
				method="GET"
				className="sensei-signup-form"
				onSubmit={ submitHandler }
			>
				<input
					type="hidden"
					name={ `gdpr[${ stepData.gdpr_field }]` }
					value="Y"
				/>

				<TextControl
					className="sensei-setup-wizard__text-control sensei-signup-form__text-control"
					placeholder={ __( 'Your email address', 'sensei-lms' ) }
					type="email"
					name="EMAIL"
					value={ email }
					onChange={ setEmail }
				/>

				<button
					disabled={ isSubmitting }
					className="sensei-setup-wizard__button sensei-setup-wizard__button--primary sensei-signup-form__button"
					type="submit"
					{ ...logLink( 'setup_wizard_newsletter_signup' ) }
				>
					{ __( 'Nice! Sign me up', 'sensei-lms' ) }
				</button>
			</form>
		</>
	);
};

export default SignupForm;
