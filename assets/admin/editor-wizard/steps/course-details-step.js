/**
 * Initial step for course creation wizard.
 *
 * @param {Object}   props
 * @param {Object}   props.data
 * @param {Function} props.setData
 */
const CourseDetailsStep = ( { data, setData } ) => {
	// Sample implementation updating title attribute.
	const onTitleChange = ( event ) => {
		setData( { ...data, title: event.target.value } );
	};
	return (
		<div>
			<div>Course Details Step</div>
			<div>
				<label htmlFor="course_title">Course title:</label>
				<input id="course_title" onChange={ onTitleChange } />
			</div>
			<div>PENDING TO IMPLEMENT</div>
		</div>
	);
};

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
				</span>{ ' ' }
				data
			</button>
			<button onClick={ goToNextStep }>Next</button>
		</div>
	);
};

export default CourseDetailsStep;
