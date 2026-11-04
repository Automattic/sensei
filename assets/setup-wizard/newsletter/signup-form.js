/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { TextControl, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { logLink } from '../../shared/helpers/log-event';
import { useSetupWizardStep } from '../data/use-setup-wizard-step';

/**
 * Sign up to Sensei Mailing list.
 *
 * Requests a subscription to Sensei's newsletter.
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

	const submitHandler = async ( e ) => {
		e.preventDefault();
		setIsSubmitting( true );
		setError( false );

		try {
			const response = await window.fetch( stepData.newsletter_url, {
				method: 'POST',
				credentials: 'omit',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams( { email, consent: '1' } ).toString(),
			} );

			if ( ! response.ok ) {
				throw new Error( 'Newsletter signup failed' );
			}
			const result = await response.json();
			if ( true !== result.success ) {
				throw new Error( 'Newsletter signup failed' );
			}

			onSubmit();
		} catch {
			setError(
				__(
					'Failed to connect to the server. Please try again later.',
					'sensei-lms'
				)
			);
		} finally {
			setIsSubmitting( false );
		}
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
				action={ stepData.newsletter_url }
				method="POST"
				className="sensei-signup-form"
				onSubmit={ submitHandler }
			>
				<input type="hidden" name="consent" value="1" />

				<TextControl
					className="sensei-setup-wizard__text-control sensei-signup-form__text-control"
					placeholder={ __( 'Your email address', 'sensei-lms' ) }
					type="email"
					name="email"
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
