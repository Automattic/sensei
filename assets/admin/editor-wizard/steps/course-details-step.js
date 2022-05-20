/**
 * Initial step for course creation wizard.
 *
 * @param {Object}   props
 * @param {Object}   props.data
 * @param {Function} props.setData
 */
const CourseDetailsStep = ( { data: wizardData, setData: setWizardData } ) => {
	// TODO Implement this.
	// Sample implementation updating newCourseTitle attribute.
	const updateNewCourseTitle = ( event ) => {
		setWizardData( { ...wizardData, newCourseTitle: event.target.value } );
	};
	return (
		<div>
			<div>
				<label htmlFor="course_title">Course title:</label>
				<input id="course_title" onChange={ updateNewCourseTitle } />
			</div>
			<div>PENDING TO IMPLEMENT</div>
		</div>
	);
};

CourseDetailsStep.Title = 'Course Details Step';

CourseDetailsStep.Actions = ( { data, goToNextStep } ) => {
	// Actions have access to the whole wizard data.
	const secondaryAction = () => {
		// TODO Remove this.
		// eslint-disable-next-line no-alert
		window.alert( `Data ${ JSON.stringify( data ) }` );
	};
	return (
		<div>
			<button onClick={ secondaryAction }>
				<span
					role="img"
					aria-label="Funny eyes that will be removed later."
				>
					👀
				</span>
			</button>
			<button onClick={ goToNextStep }>Next</button>
		</div>
	);
};

export default CourseDetailsStep;
