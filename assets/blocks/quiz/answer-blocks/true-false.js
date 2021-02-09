import { __ } from '@wordpress/i18n';
import { OptionToggle } from './option-toggle';

/**
 * Answer component for question blocks with true/false type.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {boolean}  props.attributes.rightAnswer The correct answer.
 * @param {Function} props.setAttributes
 */
const TrueFalseAnswer = ( {
	attributes: { rightAnswer = true },
	setAttributes,
} ) => {
	const options = [
		{ label: __( 'True', 'sensei-lms' ), value: true },
		{ label: __( 'False', 'sensei-lms' ), value: false },
	];
	return (
		<ul className="sensei-lms-question-block__answer sensei-lms-question-block__answer--true-false">
			{ options.map( ( { label, value } ) => (
				<li key={ value }>
					<OptionToggle
						onClick={ () =>
							setAttributes( { rightAnswer: value } )
						}
						isChecked={ rightAnswer === value }
					>
						<span>{ label }</span>
					</OptionToggle>
				</li>
			) ) }
		</ul>
	);
};

export default TrueFalseAnswer;
