/**
 * WordPress dependencies
 */
import { BaseControl, ToggleControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Answer feedback settings for the quiz block.
 *
 * @param {Object}   props
 * @param {Object}   props.options       Quiz options.
 * @param {Function} props.setAttributes
 */
const QuizAnswerFeedbackSettings = ( { options, setAttributes } ) => {
	let {
		failedShowAnswerFeedback = null,
		failedShowCorrectAnswers = null,
		failedIndicateIncorrect = null,
		allowRetakes,
	} = options;

	const hasAnyOptionSet =
		null !== failedShowAnswerFeedback ||
		null !== failedShowCorrectAnswers ||
		null !== failedIndicateIncorrect;

	failedShowAnswerFeedback = failedShowAnswerFeedback ?? ! allowRetakes;
	failedShowCorrectAnswers = failedShowCorrectAnswers ?? ! allowRetakes;
	failedIndicateIncorrect = failedIndicateIncorrect ?? ! allowRetakes;

	/**
	 * Set default values based on 'Allow Retakes' if nothing was set.
	 */
	useEffect( () => {
		if ( ! hasAnyOptionSet ) {
			setAttributes( {
				options: {
					...options,
					failedShowAnswerFeedback,
					failedShowCorrectAnswers,
					failedIndicateIncorrect,
				},
			} );
		}
	}, [
		hasAnyOptionSet,
		setAttributes,
		failedShowAnswerFeedback,
		options,
		failedShowCorrectAnswers,
		failedIndicateIncorrect,
	] );

	/**
	 * Set selected option.
	 *
	 * @param {string} optionKey
	 */
	const createChangeHandler = ( optionKey ) => ( value ) =>
		setAttributes( {
			options: {
				...options,
				[ optionKey ]: value,
			},
		} );

	return (
		<div>
			<BaseControl
				className="sensei-lms-subsection-control"
				help={ __(
					'What learners see when reviewing their quiz after grading.',
					'sensei-lms'
				) }
			>
				<strong>
					{ __( 'If learner does not pass quiz', 'sensei-lms' ) }
				</strong>
			</BaseControl>
			<ToggleControl
				checked={ failedIndicateIncorrect }
				onChange={ createChangeHandler( 'failedIndicateIncorrect' ) }
				label={ __(
					'Indicate which questions are incorrect.',
					'sensei-lms'
				) }
			/>
			<ToggleControl
				checked={ failedShowCorrectAnswers }
				onChange={ createChangeHandler( 'failedShowCorrectAnswers' ) }
				label={ __( ' Show correct answers.', 'sensei-lms' ) }
			/>
			<ToggleControl
				checked={ failedShowAnswerFeedback }
				onChange={ createChangeHandler( 'failedShowAnswerFeedback' ) }
				label={ __( 'Show “Answer Feedback” text.', 'sensei-lms' ) }
			/>
		</div>
	);
};

export default QuizAnswerFeedbackSettings;
