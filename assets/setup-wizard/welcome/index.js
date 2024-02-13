/**
 * WordPress dependencies
 */
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
/**
 * External dependencies
 */
import { WpcomTourKit } from '@automattic/tour-kit';

/**
 * Internal dependencies
 */
import { useQueryStringRouter } from '../../shared/query-string-router';
import { useSetupWizardStep } from '../data/use-setup-wizard-step';
import { H } from '../../shared/components/section';
import { HOME_PATH } from '../constants';

class ErrorBoundary extends Component {
	state = {
		hasError: false,
	};

	static getDerivedStateFromError() {
		// Update state so the next render will show the fallback UI.
		return { hasError: true };
	}

	componentDidCatch( error, errorInfo ) {
		// You can also log the error to an error reporting service
		// eslint-disable-next-line no-console
		console.error( error, errorInfo );
	}

	render() {
		if ( this.state.hasError ) {
			// You can render any custom fallback UI
			return <h1>Something went wrong.</h1>;
		}

		return this.props.children;
	}
}

/**
 * Welcome step for Setup Wizard.
 */
const Welcome = () => {
	const { goNext } = useQueryStringRouter();

	const { submitStep, isSubmitting, errorNotice } = useSetupWizardStep(
		'welcome'
	);

	const submitPage = () => {
		submitStep( {}, { onSuccess: goNext } );
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

	const config = {
		steps: [
			{
				referenceElements: {
					desktop: '.storybook__tourkit-references-a',
					mobile: '.storybook__tourkit-references-a',
				},
				meta: {
					heading: 'Laura 1',
					descriptions: {
						desktop: 'Lorem ipsum dolor sit amet.',
						mobile: 'Lorem ipsum dolor sit amet.',
					},
				},
			},
			{
				referenceElements: {
					desktop: '.storybook__tourkit-references-b',
					mobile: '.storybook__tourkit-references-b',
				},
				meta: {
					heading: 'Laura 2',
					descriptions: {
						desktop: 'Lorem ipsum dolor sit amet.',
						mobile: 'Lorem ipsum dolor sit amet.',
					},
				},
			},
			{
				referenceElements: {
					desktop: '.storybook__tourkit-references-c',
					mobile: '.storybook__tourkit-references-c',
				},
				meta: {
					heading: 'Laura 3',
					descriptions: {
						desktop: 'Lorem ipsum dolor sit amet.',
						mobile: 'Lorem ipsum dolor sit amet.',
					},
				},
			},
			{
				referenceElements: {
					desktop: '.storybook__tourkit-references-d',
					mobile: '.storybook__tourkit-references-d',
				},
				meta: {
					heading: 'Laura 4',
					descriptions: {
						desktop: 'Lorem ipsum dolor sit amet.',
						mobile: 'Lorem ipsum dolor sit amet.',
					},
				},
			},
		],
		closeHandler: () => {},
		options: {
			classNames: [ 'mytour' ],
		},
	};

	return (
		<ErrorBoundary>
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
				<WpcomTourKit config={ config } />
			</div>
		</ErrorBoundary>
	);
};

export default Welcome;
