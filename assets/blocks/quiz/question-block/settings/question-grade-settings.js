import { BaseControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

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
		<BaseControl
			id={ id }
			label={ __( 'Grade', 'sensei-lms' ) }
			className="sensei-lms-question-block__grade-control"
		>
			<div className="sensei-lms-question-block__grade-control__controls">
				<input
					id={ id }
					className=""
					type="number"
					min={ 1 }
					step={ 1 }
					value={ grade }
					onChange={ ( event ) =>
						setOptions( { grade: event.target.value } )
					}
				/>
				<Button
					isSmall
					isSecondary
					onClick={ () => setOptions( { grade: 1 } ) }
				>
					{ __( 'Reset', 'sensei-lms' ) }
				</Button>
			</div>
		</BaseControl>
	);
};

export default QuestionGradeSettings;
