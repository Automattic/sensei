/**
 * WordPress dependencies
 */
import { BaseControl, ToggleControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Answer feedback settings for the quiz block.
 *
 * @param {Object}   props
 * @param {Object}   props.options
 * @param {Function} props.setAttributes
 */
const QuizAnswerFeedbackSettings = ( { options, setAttributes } ) => {
	const failShowAnswerFeedback =
		options.failShowAnswerFeedback ?? ! options.allowRetakes;
	const failShowCorrectAnswers =
		options.failShowCorrectAnswers ?? ! options.allowRetakes;
	const failIndicateIncorrect =
		options.failIndicateIncorrect ?? ! options.allowRetakes;

	const hasAnyOptionSet =
		null !== options.failShowAnswerFeedback ||
		null !== options.failShowCorrectAnswers ||
		null !== options.failIndicateIncorrect;

	/**
	 * Reset options to their default 'unset' behavior.
	 */
	const clearExplicitOptions = () => {
		setAttributes( {
			options: {
				...options,
				failShowAnswerFeedback: null,
				failShowCorrectAnswers: null,
				failIndicateIncorrect: null,
			},
		} );
	};

	/**
	 * Set selected option, and make sure all of them become explicit.
	 *
	 * @param {string} optionKey
	 */
	const createChangeHandler = ( optionKey ) => ( value ) =>
		setAttributes( {
			options: {
				...options,
				failShowAnswerFeedback,
				failShowCorrectAnswers,
				failIndicateIncorrect,
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
				checked={ failIndicateIncorrect }
				onChange={ createChangeHandler( 'failIndicateIncorrect' ) }
				label={ __(
					'Indicate which questions are incorrect.',
					'sensei-lms'
				) }
			/>
			<ToggleControl
				checked={ failShowCorrectAnswers }
				onChange={ createChangeHandler( 'failShowCorrectAnswers' ) }
				label={ __( ' Show correct answers.', 'sensei-lms' ) }
			/>
			<ToggleControl
				checked={ failShowAnswerFeedback }
				onChange={ createChangeHandler( 'failShowAnswerFeedback' ) }
				label={ __( 'Show “Answer Feedback” text.', 'sensei-lms' ) }
			/>
			{ hasAnyOptionSet && (
				<BaseControl
					help={ __(
						"Let these settings be controlled by 'Allow Retakes'.",
						'sensei-lms'
					) }
				>
					<Button onClick={ clearExplicitOptions } isLink>
						{ __( 'Clear', 'sensei-lms' ) }
					</Button>
				</BaseControl>
			) }
			{ ! hasAnyOptionSet && (
				<BaseControl
					help={ __(
						"When unset, these are only shown if 'Allow Retakes' is off.",
						'sensei-lms'
					) }
				/>
			) }
		</div>
	);
};

export default QuizAnswerFeedbackSettings;
