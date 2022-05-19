/**
 * Final step in lesson creation wizard choosing the actual lesson pattern to use.
 *
 * @param {Object}   props
 * @param {Object}   props.data
 * @param {Function} props.setData
 * @param {Function} props.onCompletion
 */
// eslint-disable-next-line no-unused-vars
const LessonPatternsStep = ( { data, setData, onCompletion } ) => {
	// TODO Implement this.

	// We can call `onCompletion` to complete the wizard after setting the correct pattern with `setData`.

	return (
		<div>
			<div>Lesson Patterns Step</div>
			<div>PENDING TO IMPLEMENT</div>
		</div>
	);
};

LessonPatternsStep.Actions = ( { goToNextStep } ) => {
	// TODO Implement this.
	return (
		<div>
			<button onClick={ goToNextStep }>Complete</button>
		</div>
	);
};

export default LessonPatternsStep;
