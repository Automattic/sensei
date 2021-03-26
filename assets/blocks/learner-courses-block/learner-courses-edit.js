/**
 * Internal dependencies
 */
import LearnerCoursesSettings from './learner-courses-settings';

/**
 * Learner Settings component.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes         Block attributes.
 * @param {Object}   props.attributes.options Block options attribute.
 * @param {Function} props.setAttributes      Block set attributes function.
 */
const LearnerCoursesEdit = ( { attributes: { options }, setAttributes } ) => {
	const setOptions = ( editedOptions ) =>
		setAttributes( { options: { ...options, ...editedOptions } } );

	return (
		<>
			<div>Learner Courses Edit</div>
			<LearnerCoursesSettings
				options={ options }
				setOptions={ setOptions }
			/>
		</>
	);
};

export default LearnerCoursesEdit;
