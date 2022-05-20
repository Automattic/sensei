/**
 * WordPress dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Wizard component.
 *
 * @param {Object}   props
 * @param {Array}    props.steps        Array with the steps that will be rendered.
 * @param {Function} props.onStepChange Callback to call when wizard's step changes.
 * @param {Function} props.onCompletion Callback to call when wizard is completed.
 */
const Wizard = ( { steps, onStepChange, onCompletion } ) => {
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

	// Call onChange every time wizard data is changed.
	useEffect( () => {
		onStepChange( CurrentStep );
	}, [ CurrentStep, onStepChange ] );

	return (
		( CurrentStep && (
			<div className={ 'sensei-editor-wizard' }>
				<div className={ 'sensei-editor-wizard__content' }>
					<CurrentStep
						data={ data }
						setData={ setData }
						onCompletion={ onCompletion }
					/>
				</div>
				<div className={ 'sensei-editor-wizard__footer' }>
					<div className={ 'sensei-editor-wizard__progress' }>
						Step { currentStepNumber + 1 } of { steps.length }
					</div>
					{ CurrentStep.Actions && (
						<div className={ 'sensei-editor-wizard__actions' }>
							<CurrentStep.Actions
								data={ data }
								goToNextStep={ goToNextStep }
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
