/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import SingleLineInput from '../../../shared/blocks/single-line-input';
import { OptionToggle } from './option-toggle';

/**
 * Answer option in a multiple choice type question block.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes         Answer attributes.
 * @param {string}   props.attributes.label   Answer title.
 * @param {boolean}  props.attributes.correct Is this a right answer.
 * @param {Function} props.setAttributes      Update answer attributes.
 * @param {Function} props.onEnter            Add a new answer after this.
 * @param {Function} props.onRemove           Remove this answer.
 * @param {boolean}  props.hasFocus           Should this answer receive focus.
 * @param {boolean}  props.hasSelected        Is the question block selected.
 * @param {boolean}  props.isCheckbox         Should display as a checkbox
 */
const MultipleChoiceAnswerOption = ( props ) => {
	const {
		attributes: { label, correct },
		setAttributes,
		hasFocus,
		hasSelected,
		isCheckbox,
		...inputProps
	} = props;

	const ref = useRef( null );

	useEffect( () => {
		if ( hasFocus ) {
			const el = ref.current?.textarea || ref.current;
			el?.focus();
		}
	}, [ hasFocus, ref ] );

	const toggleCorrect = () => setAttributes( { correct: ! correct } );

	return (
		<div className="sensei-lms-question-block__multiple-choice-answer-option">
			<OptionToggle isChecked={ correct } isCheckbox={ isCheckbox } />
			<SingleLineInput
				ref={ ref }
				placeholder={ __( 'Add Answer', 'sensei-lms' ) }
				className="sensei-lms-question-block__multiple-choice-answer-option__input"
				onChange={ ( nextValue ) =>
					setAttributes( { label: nextValue } )
				}
				value={ label }
				{ ...inputProps }
			/>
			{ hasSelected && (
				<div className="sensei-lms-question-block__answer--multiple-choice__toggle__wrapper">
					<Button
						isPrimary
						className="sensei-lms-question-block__answer--multiple-choice__toggle"
						onClick={ toggleCorrect }
					>
						{ correct
							? __( 'Right', 'sensei-lms' )
							: __( 'Wrong', 'sensei-lms' ) }
					</Button>
				</div>
			) }
		</div>
	);
};

export default MultipleChoiceAnswerOption;
