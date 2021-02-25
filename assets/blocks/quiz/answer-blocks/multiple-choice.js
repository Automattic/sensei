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

/**
 * Default answer options for new blocks.
 */
const DEFAULT_ANSWERS = [
	{ title: '', isRight: true },
	{ title: '', isRight: false },
];

/**
 * Answer component for question blocks with multiple choice type.
 *
 * @param {Object} props
 */
const MultipleChoiceAnswer = ( props ) => {
	const {
		attributes: { answers = DEFAULT_ANSWERS },
		setAttributes,
		hasSelected,
	} = props;

	const hasMultipleRight = answers.filter( ( a ) => a.isRight ).length > 1;

	const hasDraft = ! answers[ answers.length - 1 ]?.title;

	const answerItems = [ ...answers ];
	if ( hasSelected && ! hasDraft ) {
		answerItems.push( { title: '', isRight: false } );
	}

	/**
	 * Add a new answer option.
	 *
	 * @param {number} index Answer position
	 */
	const insertAnswer = ( index ) => {
		const nextAnswers = [ ...answers ];
		const newAnswer = { title: '', isRight: false };
		nextAnswers.splice( index + 1, 0, newAnswer );
		setAttributes( {
			answers: nextAnswers,
		} );
		setFocus( index + 1 );
	};

	/**
	 * Remove an answer option.
	 *
	 * @param {number} index Answer position
	 */
	const removeAnswer = ( index ) => {
		setFocus( index - 1 );
		const nextAnswers = [ ...answers ];
		nextAnswers.splice( index, 1 );
		setAttributes( {
			answers: nextAnswers,
		} );
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
		<ol className="sensei-lms-question-block__answer sensei-lms-question-block__answer--multiple-choice">
			{ answerItems.map( ( answer, index ) => (
				<li
					key={ index }
					className={ classnames(
						'sensei-lms-question-block__answer--multiple-choice__option',
						{ 'is-draft': ! answer.title }
					) }
				>
					<MultipleChoiceAnswerOption
						hasFocus={ index === nextFocus }
						isCheckbox={ hasMultipleRight }
						attributes={ answer }
						setAttributes={ ( next ) =>
							updateAnswer( index, next )
						}
						onEnter={ () => insertAnswer( index ) }
						onRemove={ () => removeAnswer( index ) }
						{ ...{
							hasSelected,
						} }
					/>
				</li>
			) ) }
		</ol>
	);
};

export default MultipleChoiceAnswer;
