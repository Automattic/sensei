import { FullScreen } from './fullscreen.jsx';
import { render } from '@wordpress/element';
import './onboarding.scss';

function SenseiOnboardingPage() {

	return (
		<FullScreen>
			<div className="sensei-onboarding__header">

			</div>
			<div className="sensei-onboarding__container">
				
			</div>
		</FullScreen>
	);
}

render(
	<SenseiOnboardingPage />,
	document.getElementById( 'sensei-onboarding-page' )
);
