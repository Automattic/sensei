/**
 * Upgrade step during course creation wizard.
 */
const UpgradeStep = () => {
	// TODO Implement this.
	return (
		<div>
			<div>PENDING TO IMPLEMENT</div>
		</div>
	);
};

UpgradeStep.Title = 'Upgrade Step';

UpgradeStep.Actions = ( { goToNextStep } ) => {
	// TODO Implement this.
	return (
		<div>
			<button onClick={ goToNextStep }>Next</button>
			<a href="https://senseilms.com" target="_blank" rel="noreferrer">
				Upgrade
			</a>
		</div>
	);
};

export default UpgradeStep;
