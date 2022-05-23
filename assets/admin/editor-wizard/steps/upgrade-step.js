/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';

/**
 * Upgrade step during course creation wizard.
 */
const UpgradeStep = () => {
	return (
		<div className="sensei-editor-wizard-modal__columns">
			<div className="sensei-editor-wizard-modal__content">
				<h1>Sell with Sensei Pro</h1>
				<p>
					Do you want to sell this course? This requires Sensei Pro
					which also unlocks many useful features.
				</p>
				<h2>$149 USD</h2>
				<span className="label">per year, 1 site</span>
				<ul className="sensei-editor-wizard-modal__upsell-features">
					<li className="sensei-editor-wizard-modal__upsell-feature-item">
						WooCommerce integration
					</li>
					<li className="sensei-editor-wizard-modal__upsell-feature-item">
						Schedule &apos;drip&apos; content
					</li>
					<li className="sensei-editor-wizard-modal__upsell-feature-item">
						Set expiration date of courses
					</li>
					<li className="sensei-editor-wizard-modal__upsell-feature-item">
						Quiz timer
					</li>
					<li className="sensei-editor-wizard-modal__upsell-feature-item">
						Flashcards, Image Hotspots, and Checklists
					</li>
					<li className="sensei-editor-wizard-modal__upsell-feature-item">
						1 year of updates & support
					</li>
				</ul>
			</div>
			<div className="sensei-editor-wizard-modal__illustration">
				<img
					src={
						window.sensei.pluginUrl +
						'/assets/images/sensei-pro-upsell.png'
					}
					alt="Illustration of a course listing with the pricing defined and with the button 'Purchase Button'"
					height="75%"
				/>
			</div>
		</div>
	);
};

UpgradeStep.Actions = ( { goToNextStep } ) => {
	const upgrade = () => {
		window.open( '', 'sensei-pricing', 'noreferrer' );
		goToNextStep();
	};
	return (
		<div>
			<Button isTertiary onClick={ goToNextStep }>
				Continue with Sensei Free
			</Button>
			<Button isPrimary onClick={ upgrade } target="_blank">
				Get Sensei Pro
			</Button>
		</div>
	);
};

export default UpgradeStep;
