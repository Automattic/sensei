import { TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Question block answer feedback settings control.
 *
 * @param {Object}   props
 * @param {Object}   props.options
 * @param {string}   props.options.answerFeedback Learner feedback.
 * @param {Function} props.setOptions
 */
const QuestionAnswerFeedbackSettings = ( {
	options: { answerFeedback },
	setOptions,
} ) => (
	<TextareaControl
		label={ __( 'Answer Feedback', 'sensei-lms' ) }
		onChange={ ( value ) => setOptions( { answerFeedback: value } ) }
		value={ answerFeedback }
		help={ __(
			'Displayed to the user after the quiz has been graded.',
			'sensei-lms'
		) }
	/>
);

export default QuestionAnswerFeedbackSettings;
