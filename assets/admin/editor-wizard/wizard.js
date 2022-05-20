/**
 * WordPress dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Wizard component.
 *
 * @param {Object}   props
 * @param {Array}    props.steps        Array with the steps that will be rendered.
 * @param {Function} props.onChange     Callback to call when wizard data changes.
 * @param {Function} props.onCompletion Callback to call when wizard is completed.
 */
const Wizard = ( { steps, onChange, onCompletion } ) => {
	const [ currentStepNumber, setCurrentStepNumber ] = useState( 0 );
	const [ data, setData ] = useState( {} );

	// Call onChange every time wizard data is changed.
	useEffect( () => {
		onChange( data );
	}, [ data, onChange ] );

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
