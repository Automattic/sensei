/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
/**
 * Internal dependencies
 */
import { QuestionGradeControl } from '../question-grade-control';
/**
 * Internal dependencies
 */

/**
 * Internal dependencies
 */

/**
 * Question block grade settings.
 *
 * @param {Object}   props
 * @param {Object}   props.options
 * @param {number}   props.options.grade Question grade.
 * @param {Function} props.setOptions
 */
const QuestionGradeSettings = ( { options: { grade = 1 }, setOptions } ) => {
	return (
		<QuestionGradeControl
			label={ __( 'Grade', 'sensei-lms' ) }
			value={ grade }
			onChange={ ( nextGrade ) =>
				setOptions( { grade: nextGrade ?? 1 } )
			}
			allowReset={ true }
		/>
	);
};

export default QuestionGradeSettings;
