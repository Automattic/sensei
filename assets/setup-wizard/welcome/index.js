/**
 * WordPress dependencies
 */
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useQueryStringRouter } from '../../shared/query-string-router';
import { useSetupWizardStep } from '../data/use-setup-wizard-step';
import { H } from '../../shared/components/section';
import { HOME_PATH } from '../constants';

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

	/**
	 * Filters the title from the Welcome step in the Setup Wizard.
	 *
	 * @since 4.8.0
	 *
	 * @param {string} title Title text.
	 *
	 * @return {string} Filtered title text.
	 */
	const title = applyFilters(
		'sensei.setupWizard.welcomeTitle',
		__( 'Welcome to Sensei LMS', 'sensei-lms' )
	);

	/**
	 * Filters the paragraph from the Welcome step in the Setup Wizard.
	 *
	 * @since 4.8.0
	 *
	 * @param {string} paragraph Paragraph text.
	 *
	 * @return {string} Filtered paragraph text.
	 */
	const paragraph = applyFilters(
		'sensei.setupWizard.welcomeParagraph',
		__(
			'Let’s set up your site to launch your first course.',
			'sensei-lms'
		)
	);

	return (
		<div className="sensei-setup-wizard__full-centered-step">
			<div className="sensei-setup-wizard__full-centered-content">
				<H className="sensei-setup-wizard__step-title">{ title }</H>
				<p>{ paragraph }</p>
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
						<a href={ HOME_PATH }>
							{ __( 'Skip onboarding', 'sensei-lms' ) }
						</a>
					</div>
				</div>
			</div>
		</div>
	);
};

export default Welcome;
