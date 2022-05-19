/**
 * Upgrade step during course creation wizard.
 *
 * @param {Object} props
 */
const UpgradeStep = ( {} ) => {
	// TODO Implement this.
	return (
		<div>
			<div>Upgrade Step</div>
			<div>PENDING TO IMPLEMENT</div>
		</div>
	);
};

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
