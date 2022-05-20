/**
 * Initial step for course creation wizard.
 */
const LessonDetailsStep = () => {
	// TODO Implement this.
	return (
		<div>
			<div>PENDING TO IMPLEMENT</div>
		</div>
	);
};

LessonDetailsStep.Title = 'Lesson Details Step';

LessonDetailsStep.Actions = ( { goToNextStep } ) => {
	return (
		<div>
			<button onClick={ goToNextStep }>Next</button>
		</div>
	);
};

export default LessonDetailsStep;
