/**
 * Internal dependencies
 */
import PatternsList from '../patterns-list';

/**
 * Final step in course creation wizard choosing the actual course pattern to use.
 *
 * @param {Object}   props
 * @param {Object}   props.data
 * @param {Function} props.setData
 * @param {Function} props.onCompletion
 */
/* eslint-disable no-unused-vars */
const CoursePatternsStep = ( {
	data: wizardData,
	setData: setWizardData,
	onCompletion,
} ) => {
	// TODO Implement this.

	// We can call `onCompletion` to complete the wizard after setting the correct pattern with `setData`.
	// We could replace `onCompletion` with the `goToNextStep` callback with a similar effect.

	return (
		<div>
			<div>PENDING TO IMPLEMENT</div>
			<PatternsList />
		</div>
	);
};

CoursePatternsStep.Title = 'Course Patterns Step';

CoursePatternsStep.Actions = ( { goToNextStep } ) => {
	// TODO Implement this.
	return (
		<div>
			<button onClick={ goToNextStep }>Complete</button>
		</div>
	);
};

export default CoursePatternsStep;
