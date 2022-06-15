/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useLogEvent } from './helpers';

/**
 * Wizard component.
 *
 * @param {Object}   props
 * @param {Array}    props.steps           Array with the steps that will be rendered.
 * @param {Array}    props.wizardDataState Wizard Data and wizard data setter.
 * @param {Function} props.onCompletion    Callback to call when wizard is completed.
 * @param {Function} props.skipWizard      Function to skip wizard.
 */
const Wizard = ( { steps, wizardDataState, onCompletion, skipWizard } ) => {
	const [ currentStepNumber, setCurrentStepNumber ] = useState( 0 );
	const [ wizardData, setWizardData ] = wizardDataState;
	const logEvent = useLogEvent();

	const goToNextStep = () => {
		const nextStepNumber = currentStepNumber + 1;

		if ( nextStepNumber < steps.length ) {
			setCurrentStepNumber( nextStepNumber );
			logEvent( 'editor_wizard_navigate_to_next_step', {
				navigated_to: steps[ nextStepNumber ].name,
			} );
		} else {
			onCompletion( wizardData );
		}
	};

	const CurrentStep = steps[ currentStepNumber ];

	return (
		<div className="sensei-editor-wizard">
			<CurrentStep
				wizardData={ wizardData }
				setWizardData={ setWizardData }
				onCompletion={ onCompletion }
			/>
			<div className="sensei-editor-wizard__footer">
				<div className="sensei-editor-wizard__progress">
					{ sprintf(
						// translators: %1$d Current step number, %2$d Number of steps.
						__( 'Step %1$d of %2$d', 'sensei-lms' ),
						currentStepNumber + 1,
						steps.length
					) }
				</div>
				{ CurrentStep.Actions && (
					<div className="sensei-editor-wizard__actions">
						<CurrentStep.Actions
							goToNextStep={ goToNextStep }
							skipWizard={ skipWizard }
						/>
					</div>
				) }
			</div>
		</div>
	);
};

export default Wizard;
