/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Wizard component.
 *
 * @param {Object}   props
 * @param {Array}    props.steps        Array with the steps that will be rendered.
 * @param {Function} props.onCompletion Callback to call when wizard is completed.
 * @param {Function} props.skipWizard   Function to skip wizard.
 */
const Wizard = ( { steps, onCompletion, skipWizard } ) => {
	const [ currentStepNumber, setCurrentStepNumber ] = useState( 0 );
	const [ data, setData ] = useState( {} );

	const goToNextStep = () => {
		if ( currentStepNumber + 1 < steps.length ) {
			setCurrentStepNumber( currentStepNumber + 1 );
		} else {
			onCompletion( data );
		}
	};

	const CurrentStep = steps[ currentStepNumber ];

	return (
		( CurrentStep && (
			<div className="sensei-editor-wizard">
				<CurrentStep
					data={ data }
					setData={ setData }
					onCompletion={ onCompletion }
				/>
				<div className="sensei-editor-wizard__footer">
					<div className="sensei-editor-wizard__progress">
						Step { currentStepNumber + 1 } of { steps.length }
					</div>
					{ CurrentStep.Actions && (
						<div className="sensei-editor-wizard__actions">
							<CurrentStep.Actions
								data={ data }
								goToNextStep={ goToNextStep }
								skipWizard={ skipWizard }
							/>
						</div>
					) }
				</div>
			</div>
		) ) ||
		null
	);
};

export default Wizard;
