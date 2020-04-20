import { __ } from '@wordpress/i18n';

import { Button } from '@wordpress/components';
import { Card } from '@woocommerce/components';
import '@woocommerce/components/build-style/style.css';

function SenseiOnboardingPage() {
	return (
		<Card className="wrap">
			<h1> { __( 'Welcome to Sensei LMS!', 'sensei-lms' ) } </h1>
			<Button isPrimary>Okay</Button>
		</Card>
	);
}

wp.element.render(
	<SenseiOnboardingPage />,
	document.getElementById( 'sensei-onboarding-page' )
);
