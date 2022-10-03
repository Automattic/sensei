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
	const { goTo } = useQueryStringRouter();

	const goToNextStep = () => {
		goTo( 'features' );
	};

	return (
		<div className="sensei-setup-wizard__columns">
			<div className="sensei-setup-wizard__columns-content">
				<H className="sensei-setup-wizard__step-title">
					{ __(
						'Be the first to know about new features',
						'sensei-lms'
					) }
				</H>
				<p>
					{ __(
						'Sensei is growing fast and we’re constantly releasing new features. Join on our mailing list to know first.',
						'sensei-lms'
					) }
				</p>
				<div className="sensei-setup-wizard__actions sensei-setup-wizard__actions--full-width">
					<SignupForm onSubmit={ goToNextStep } />
					<div className="sensei-setup-wizard__action-skip">
						<button
							className="sensei-setup-wizard__button sensei-setup-wizard__button--link"
							onClick={ goToNextStep }
						>
							{ __( 'Skip newsletter signup', 'sensei-lms' ) }
						</button>
					</div>
				</div>
			</div>
			<div
				className="sensei-setup-wizard__columns-illustration sensei-setup-wizard__newsletter-illustration"
				aria-hidden="true"
			>
				<img
					className="sensei-setup-wizard__columns-illustration-image"
					src={
						window.sensei.imagesPath +
						'onboarding-newsletter-illustration.png'
					}
					alt=""
				/>
			</div>
		</div>
	);
};

export default Newsletter;
