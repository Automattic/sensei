import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Question block settings for multiple choice questions.
 *
 * @param {Object}   props
 * @param {Object}   props.options
 * @param {boolean}  props.options.randomAnswerOrder Display answer options in random order.
 * @param {Function} props.setOptions
 */
const QuestionMultipleChoiceSettings = ( {
	options: { randomAnswerOrder = true },
	setOptions,
} ) => (
	<CheckboxControl
		label={ __( 'Random Order', 'sensei-lms' ) }
		checked={ randomAnswerOrder }
		onChange={ ( value ) => setOptions( { randomAnswerOrder: value } ) }
	/>
);

export default QuestionMultipleChoiceSettings;
