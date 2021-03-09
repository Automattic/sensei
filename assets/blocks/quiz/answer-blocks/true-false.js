/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { OptionToggle } from './option-toggle';

/**
 * Answer component for question blocks with true/false type.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {boolean}  props.attributes.correct The correct answer.
 * @param {Function} props.setAttributes
 * @param {boolean}  props.hasSelected        Is question block selected.
 */
const TrueFalseAnswer = ( {
	attributes: { correct = true },
	setAttributes,
	hasSelected,
} ) => {
	const options = [
		{ label: __( 'True', 'sensei-lms' ), value: true },
		{ label: __( 'False', 'sensei-lms' ), value: false },
	];
	return (
		<ul className="sensei-lms-question-block__answer sensei-lms-question-block__answer--true-false">
			{ options.map( ( { label, value } ) => (
				<li
					key={ value }
					className="sensei-lms-question-block__answer--true-false__option"
				>
					<OptionToggle isChecked={ correct === value }>
						<span>{ label }</span>
					</OptionToggle>
					{ hasSelected && (
						<div className="sensei-lms-question-block__answer--multiple-choice__toggle__wrapper">
							<Button
								isPrimary
								className="sensei-lms-question-block__answer--true-false__toggle"
								onClick={ () =>
									setAttributes( {
										correct:
											value === correct ? ! value : value,
									} )
								}
							>
								{ correct === value
									? __( 'Right', 'sensei-lms' )
									: __( 'Wrong', 'sensei-lms' ) }
							</Button>
						</div>
					) }
				</li>
			) ) }
		</ul>
	);
};

/**
 * Read-only answer component true/false question block.
 *
 * @param {Object}  props
 * @param {Object}  props.attributes
 * @param {boolean} props.attributes.correct The correct answer.
 */
TrueFalseAnswer.view = ( { attributes: { correct = true } } ) => {
	const options = [
		{ label: __( 'True', 'sensei-lms' ), value: true },
		{ label: __( 'False', 'sensei-lms' ), value: false },
	];
	return (
		<ul className="sensei-lms-question-block__answer sensei-lms-question-block__answer--true-false">
			{ options.map( ( { label, value } ) => (
				<li
					key={ value }
					className="sensei-lms-question-block__answer--true-false__option"
				>
					<OptionToggle isChecked={ correct === value }>
						<span>{ label }</span>
					</OptionToggle>
				</li>
			) ) }
		</ul>
	);
};

export default TrueFalseAnswer;
