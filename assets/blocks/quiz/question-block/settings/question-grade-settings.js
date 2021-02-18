/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import NumberControl from '../../../editor-components/number-control';

/**
 * Question block grade settings.
 *
 * @param {Object}   props
 * @param {Object}   props.options
 * @param {number}   props.options.grade Question grade.
 * @param {Function} props.setOptions
 * @param {string}   props.clientId
 */
const QuestionGradeSettings = ( {
	options: { grade = 1 },
	setOptions,
	clientId,
} ) => {
	const id = `sensei-lms-question-block__grade-control-${ clientId }`;
	return (
		<NumberControl
			id={ id }
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
