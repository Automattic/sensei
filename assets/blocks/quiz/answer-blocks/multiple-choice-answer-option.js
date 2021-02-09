import { Button } from '@wordpress/components';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ENTER, BACKSPACE } from '@wordpress/keycodes';
import SingleLineInput from '../../course-outline/single-line-input';
import { OptionToggle } from './option-toggle';

/**
 * Answer option in a multiple choice type question block.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes         Answer attributes.
 * @param {string}   props.attributes.title   Answer title.
 * @param {boolean}  props.attributes.isRight Is this a right answer.
 * @param {Function} props.setAttributes      Update answer attributes.
 * @param {Function} props.add                Add a new answer after this.
 * @param {Function} props.remove             Remove this answer.
 * @param {boolean}  props.hasFocus           Should this answer receive focus.
 * @param {boolean}  props.hasSelected        Is the question block selected.
 * @param {boolean}  props.isCheckbox         Should display as a checkbox
 */
const MultipleChoiceAnswerOption = ( props ) => {
	const {
		attributes: { title, isRight },
		setAttributes,
		insertAnswerAfter,
		removeAnswer,
		hasFocus,
		hasSelected,
		isCheckbox,
	} = props;

	const onKeyDown = ( e ) => {
		switch ( e.keyCode ) {
			case ENTER:
				e.preventDefault();
				insertAnswerAfter();

				break;
			case BACKSPACE:
				if ( ! title?.length ) {
					e.preventDefault();
					removeAnswer();
				}
				break;
		}
	};

	const ref = useRef( null );

	useEffect( () => {
		if ( hasFocus ) {
			ref.current?.focus();
		}
	}, [ hasFocus, ref ] );

	const toggleRight = () => setAttributes( { isRight: ! isRight } );

	return (
		<div className="sensei-lms-question-block__multiple-choice-answer-option">
			<OptionToggle
				onClick={ toggleRight }
				isChecked={ isRight }
				isCheckbox={ isCheckbox }
			/>
			<SingleLineInput
				ref={ ref }
				placeholder={ __( 'Add Answer', 'sensei-lms' ) }
				className="sensei-lms-question-block__multiple-choice-answer-option__input"
				onChange={ ( nextValue ) =>
					setAttributes( { title: nextValue } )
				}
				value={ title }
				onKeyDown={ onKeyDown }
			/>
			{ hasSelected && (
				<Button
					className="sensei-lms-question-block__multiple-choice-answer-option__hint"
					onClick={ toggleRight }
				>
					{ isRight
						? __( 'Right', 'sensei-lms' )
						: __( 'Wrong', 'sensei-lms' ) }
				</Button>
			) }
		</div>
	);
};

export default MultipleChoiceAnswerOption;
