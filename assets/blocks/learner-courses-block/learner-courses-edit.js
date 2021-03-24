/**
 * Internal dependencies
 */
import LearnerCoursesSettings from './learner-courses-settings';

/**
 * Learner Settings component.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Block set attributes function.
 */
const LearnerCoursesEdit = ( { attributes, setAttributes } ) => (
	<>
		<div>Learner Courses Edit</div>
		<LearnerCoursesSettings
			attributes={ attributes }
			setAttributes={ setAttributes }
		/>
	</>
);

export default LearnerCoursesEdit;
