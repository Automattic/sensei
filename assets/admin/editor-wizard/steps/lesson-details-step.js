/**
 * Initial step for course creation wizard.
 *
 * @param {Object}   props
 * @param {Object}   props.data
 * @param {Function} props.setData
 */
const LessonDetailsStep = ( { data, setData } ) => {
	const onTitleChange = ( event ) => {
		setData( { ...data, title: event.target.value } );
	};
	return (
		<div>
			<div>Lesson Details Step</div>
			<div>
				<label htmlFor="course_title">Course title:</label>
				<input id="course_title" onChange={ onTitleChange } />
			</div>
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
