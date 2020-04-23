import { Card, H } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

export function Welcome() {
	return (
		<>
			<div className="sensei-onboarding__title">
				<H> { __( 'Welcome to Sensei LMS!', 'sensei-lms' ) } </H>
			</div>
			<Card className="sensei-onboarding__card">
				<p>
					{ __( 'Thank you for choosing Sensei LMS!', 'sensei-lms' ) }
				</p>
				<p>
					{ __(
						'This setup wizard will help you get started creating online courses more quickly. It is optional and should only take a few minutes.',
						'sensei-lms'
					) }
				</p>
				<Button isPrimary className="sensei-button">
					{ __( 'Continue', 'sensei-lms' ) }
				</Button>
			</Card>
			<div className="sensei-onboarding__skip">
				<Button isLink>{ __( 'Not right now', 'sensei-lms' ) }</Button>
			</div>
		</>
	);
}
