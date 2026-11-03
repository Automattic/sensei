/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SignupForm from './signup-form';
import { useQueryStringRouter } from '../../shared/query-string-router';
import { H } from '../../shared/components/section';

/**
 * Newsletter step for Setup Wizard.
 */
const Newsletter = () => {
	const { goNext } = useQueryStringRouter();

	return (
		<>
			<div className="sensei-setup-wizard__content">
				<H className="sensei-setup-wizard__step-title">
					{ __( 'Get more from Sensei', 'sensei-lms' ) }
				</H>
				<p>
					{ __(
						'Get course creation tips, helpful resources, and Sensei product updates. After signing up, check your inbox to confirm your subscription.',
						'sensei-lms'
					) }
				</p>
				<div className="sensei-setup-wizard__actions sensei-setup-wizard__actions--full-width">
					<SignupForm onSubmit={ goNext } />
					<div className="sensei-setup-wizard__action-skip">
						<button
							className="sensei-setup-wizard__button sensei-setup-wizard__button--link"
							onClick={ goNext }
						>
							{ __( 'Skip newsletter signup', 'sensei-lms' ) }
						</button>
					</div>
				</div>
			</div>
			<div
				className="sensei-setup-wizard__illustration sensei-setup-wizard__illustration--newsletter"
				aria-hidden="true"
			/>
		</>
	);
};

export default Newsletter;
