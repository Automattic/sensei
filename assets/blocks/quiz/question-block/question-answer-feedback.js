/**
 * WordPress dependencies
 */
import { TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';



/**
 * Question Answer Feedback control.
 *
 * @param {string}   questionType             Question type.
 * @param {Object}   props                    Block props.
 * @param {Object}   props.attributes         Block attributes.
 * @param {string}   props.attributes.options Block options attribute.
 * @param {Function} props.setAttributes      Update block attributes.
 */
const QuestionAnswerFeedback = ( {
	questionType,
	attributes: { options = {} },
	setAttributes,
	...props
} ) => {
	const theFeedback = options.answerFeedback
	const setOptions = ( next ) =>
		setAttributes( { options: { ...options, ...next } } );

	if ( 'multiple-choice' == questionType || 'gap-fill' == questionType || 'boolean' == questionType ) {
		return (
			<TextareaControl
				label={ __( 'Answer Feedback', 'sensei-lms' ) }
				onChange={ ( value ) => setOptions( { answerFeedback: value } ) }
				value={ theFeedback || '' }
				help={ __(
					'Displayed to the learner after the quiz has been graded.',
					'sensei-lms'
				) }
			/>
		);
	} else {
		return false;
	}
};

export default QuestionAnswerFeedback;