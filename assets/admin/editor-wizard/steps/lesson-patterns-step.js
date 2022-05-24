/**
 * Internal dependencies
 */
import PatternsList from '../patterns-list';

/**
 * Final step in lesson creation wizard choosing the actual lesson pattern to use.
 *
 * @param {Object}   props
 * @param {Object}   props.data
 * @param {Function} props.onCompletion
 */
const LessonPatternsStep = ( { data, onCompletion } ) => (
	<div className="sensei-editor-wizard-modal__content">
		<PatternsList data={ data } onChoose={ onCompletion } />
	</div>
);

LessonPatternsStep.Actions = ( { goToNextStep } ) => {
	// TODO Implement this.
	return (
		<div>
			<button onClick={ goToNextStep }>Complete</button>
		</div>
	);
};

export default LessonPatternsStep;
