import { Stepper } from '@woocommerce/components';
import { FullScreen } from './fullscreen.jsx';
import { render } from '@wordpress/element';
import './onboarding.scss';
import { steps } from './steps.jsx';

function SenseiOnboardingPage() {
	const currentStep = steps[ 0 ];
	return (
		<FullScreen>
			<div className="sensei-onboarding__header">
				<Stepper currentStep={ currentStep.key } steps={ steps } />
			</div>
			<div className="sensei-onboarding__container">
				{ currentStep.container }
			</div>
		</FullScreen>
	);
}

render(
	<SenseiOnboardingPage />,
	document.getElementById( 'sensei-onboarding-page' )
);
