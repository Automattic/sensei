/**
 * Internal dependencies
 */
import PatternsList from '../patterns-list';

/**
 * Final step in course creation wizard choosing the actual course pattern to use.
 *
 * @param {Object}   props
 * @param {Object}   props.data
 * @param {Function} props.onCompletion
 */
const CoursePatternsStep = ( { data, onCompletion } ) => (
	<div className="sensei-editor-wizard-modal__content">
		<PatternsList
			title={ data.newCourseTitle }
			description={ data.newCourseDescription }
			onChoose={ onCompletion }
		/>
	</div>
);

CoursePatternsStep.Actions = ( { goToNextStep } ) => {
	// TODO Implement this.
	return (
		<div>
			<button onClick={ goToNextStep }>Complete</button>
		</div>
	);
};

export default CoursePatternsStep;
