/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useQueryStringRouter } from '../../shared/query-string-router';
import { H } from '../../shared/components/section';

/**
 * Newsletter step for Setup Wizard.
 */
const Newsletter = () => {
	const { goTo } = useQueryStringRouter();

	return (
		<div className="sensei-setup-wizard__columns">
			<div className="sensei-setup-wizard__columns-content sensei-setup-wizard__slide-in-from-bottom-animation">
				<H className="sensei-setup-wizard__step-title">
					{ __(
						'Be the first to know about new features',
						'sensei-lms'
					) }
				</H>
				<p>
					{ __(
						'Sensei is growing fast and we’re constantly releasing new features. Join on our mailing list to find out about everything that we release and how you can make the most out of it.',
						'sensei-lms'
					) }
				</p>
				<div className="sensei-setup-wizard__actions">
					<button
						className="sensei-setup-wizard__button sensei-setup-wizard__button--primary"
						onClick={ () => {
							goTo( 'features' );
						} }
					>
						{ __( 'Nice! Sign me up', 'sensei-lms' ) }
					</button>
					<div className="sensei-setup-wizard__action-skip">
						<button
							className="sensei-setup-wizard__button sensei-setup-wizard__button--link"
							onClick={ () => {
								goTo( 'features' );
							} }
						>
							{ __( 'Skip newsletter signup', 'sensei-lms' ) }
						</button>
					</div>
				</div>
			</div>
			<div
				className="sensei-setup-wizard__newsletter-illustration"
				aria-hidden="true"
			></div>
		</div>
	);
};

export default Newsletter;
