/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';

/**
 * Upgrade step during course creation wizard.
 */
const UpgradeStep = () => {
	return (
		<div className="components-modal-columns">
			<div className="components-modal-columns__description">
				<p>
					Do you want to sell this course? This requires Sensei Pro
					which also unlocks many useful features.
				</p>
				<h2>$149 USD</h2>
				<span className="label">per year, 1 site</span>
				<ul>
					<li>WooCommerce integration</li>
					<li>Schedule &apos;drip&apos; content</li>
					<li>Set expiration date of courses</li>
					<li>Quiz timer</li>
					<li>Flashcards, Image Hotspots, and CHecklists</li>
					<li>1 year of updates & support</li>
				</ul>
			</div>
			<div className="components-modal-columns__image">
				Image container
			</div>
		</div>
	);
};

UpgradeStep.Title = 'Sell with Sensei Pro';

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
