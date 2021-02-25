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
 * @param {boolean}  props.attributes.rightAnswer The correct answer.
 * @param {Function} props.setAttributes
 * @param {boolean}  props.hasSelected            Is question block selected.
 */
const TrueFalseAnswer = ( {
	attributes: { rightAnswer = true },
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
					<OptionToggle
						onClick={ () =>
							setAttributes( { rightAnswer: value } )
						}
						isChecked={ rightAnswer === value }
					>
						<span>{ label }</span>
					</OptionToggle>
					{ hasSelected && (
						<div className="sensei-lms-question-block__answer--multiple-choice__toggle__wrapper">
							<Button
								isPrimary
								className="sensei-lms-question-block__answer--true-false__toggle"
								onClick={ () =>
									setAttributes( {
										rightAnswer:
											value === rightAnswer
												? ! value
												: value,
									} )
								}
							>
								{ rightAnswer === value
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

export default TrueFalseAnswer;
