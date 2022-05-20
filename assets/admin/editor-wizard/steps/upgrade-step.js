/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Upgrade step during course creation wizard.
 *
 * @param {Object}   props
 * @param {Object}   props.data
 * @param {Function} props.setData
 */
const UpgradeStep = ( { data: wizardData, setData: setWizardData } ) => {
	// Update modal title.
	useEffect( () => {
		setWizardData( { ...wizardData, modalTitle: 'Upgrade Step' } );
	}, [] );
	// TODO Implement this.
	return (
		<div>
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
