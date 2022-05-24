/**
 * Initial step for course creation wizard.
 */
const LessonDetailsStep = () => {
	// TODO Implement this.
	return (
		<div className="sensei-editor-wizard-modal__columns">
			<div className="sensei-editor-wizard-modal__content">
				<h1>Lesson Details Step</h1>
				<div>PENDING TO IMPLEMENT</div>
			</div>
			<div className="sensei-editor-wizard-modal__illustration">
				<img
					src={
						window.sensei.pluginUrl +
						'assets/dist/images/sensei-pro-upsell.png'
					}
					alt="PENDING TO IMPLEMENT, BUT HERE TO SHOW IT WORKING"
					className="sensei-editor-wizard-modal__illustration-image"
				/>
			</div>
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
