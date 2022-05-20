/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Initial step for course creation wizard.
 *
 * @param {Object}   props
 * @param {Object}   props.data
 * @param {Function} props.setData
 */
const LessonDetailsStep = ( { data: wizardData, setData: setWizardData } ) => {
	// Update modal title.
	useEffect( () => {
		setWizardData( { ...wizardData, modalTitle: 'Lesson Details Step' } );
	}, [] );

	return (
		<div>
			<div>PENDING TO IMPLEMENT</div>
		</div>
	);
};

LessonDetailsStep.Actions = ( { goToNextStep } ) => {
	return (
		<div>
			<button onClick={ goToNextStep }>Next</button>
		</div>
	);
};

export default LessonDetailsStep;
