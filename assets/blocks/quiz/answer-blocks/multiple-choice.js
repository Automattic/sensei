/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';

/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import MultipleChoiceAnswerOption from './multiple-choice-answer-option';
import { OptionToggle } from './option-toggle';

/**
 * Default answer options for new blocks.
 */
const DEFAULT_ANSWERS = [
	{ label: '', correct: true },
	{ label: '', correct: false },
];

/**
 * Check if there are more than one right answers.
 *
 * @param {Array} answers
 */
const hasMultipleRightAnswers = ( answers ) =>
	answers.filter( ( a ) => a.correct ).length > 1;

/**
 * Answer component for question blocks with multiple choice type.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes
 * @param {Function} props.setAttributes
 * @param {Array}    props.attributes.answers Answers.
 */
const MultipleChoiceAnswer = ( props ) => {
	const { setAttributes, hasSelected } = props;

	let {
		attributes: { answers = [] },
	} = props;

	if ( 0 === answers.length ) {
		answers = DEFAULT_ANSWERS;
	}

	const hasMultipleRight = hasMultipleRightAnswers( answers );

	const hasDraft = ! answers[ answers.length - 1 ]?.label;

	const answerItems = [ ...answers ];
	if ( hasSelected && ! hasDraft ) {
		answerItems.push( { label: '', correct: false } );
	}

	/**
	 * Add a new answer option.
	 *
	 * @param {number} index Answer position
	 */
	const insertAnswer = ( index ) => {
		const nextAnswers = [ ...answers ];
		const newAnswer = { label: '', correct: false };
		nextAnswers.splice( index + 1, 0, newAnswer );
		setAttributes( { answers: nextAnswers } );
		setFocus( index + 1 );
	};

	/**
	 * Remove an answer option.
	 *
	 * @param {number} index Answer position
	 */
	const removeAnswer = ( index ) => {
		// Do not allow the user to remove all the answers.
		if ( answers.length === 1 ) {
			return;
		}

		setFocus( index - 1 );
		const nextAnswers = [ ...answers ];
		nextAnswers.splice( index, 1 );
		setAttributes( { answers: nextAnswers } );
	};

	/**
	 * Update answer attributes.
	 *
	 * @param {number} index Answer position
	 * @param {Object} next  Updated answer
	 */
	const updateAnswer = ( index, next ) => {
		{
			const nextAnswers = [ ...answers ];
			nextAnswers[ index ] = { ...nextAnswers[ index ], ...next };
			setAttributes( { answers: nextAnswers } );
		}
	};

	const [ nextFocus, setFocus ] = useState( null );

	return (
		<MultipleChoiceAnswer.Options answers={ answerItems }>
			{ ( answer, index ) => (
				<MultipleChoiceAnswerOption
					hasFocus={ index === nextFocus }
					isCheckbox={ hasMultipleRight }
					attributes={ answer }
					setAttributes={ ( next ) => updateAnswer( index, next ) }
					onEnter={ () => insertAnswer( index ) }
					onRemove={ () => removeAnswer( index ) }
					{ ...{
						hasSelected,
					} }
				/>
			) }
		</MultipleChoiceAnswer.Options>
	);
};

/**
 * Render a list of answer options.
 *
 * @param {Object}   props
 * @param {Array}    props.answers  Answer list.
 * @param {Function} props.children Answer render function
 */
MultipleChoiceAnswer.Options = ( { answers, children } ) => (
	<ol className="sensei-lms-question-block__answer sensei-lms-question-block__answer--multiple-choice">
		{ answers.map( ( answer, index ) => (
			<li
				key={ index }
				className={ classnames(
					'sensei-lms-question-block__answer--multiple-choice__option',
					{ 'is-draft': ! answer.label }
				) }
			>
				{ children( answer, index ) }
			</li>
		) ) }
	</ol>
);

/**
 * Read-only multiple choice answer component.
 *
 * @param {Object} props
 * @param {Object} props.attributes
 * @param {Array}  props.attributes.answers Answers.
 */
MultipleChoiceAnswer.view = ( { attributes: { answers = [] } } ) => {
	const hasMultipleRight = hasMultipleRightAnswers( answers );

	return (
		<MultipleChoiceAnswer.Options answers={ answers }>
			{ ( answer ) => (
				<>
					<OptionToggle
						isChecked={ answer.correct }
						isCheckbox={ hasMultipleRight }
					/>
					{ answer.label }
				</>
			) }
		</MultipleChoiceAnswer.Options>
	);
};

export default MultipleChoiceAnswer;
