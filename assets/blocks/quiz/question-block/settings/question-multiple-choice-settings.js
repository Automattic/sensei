/**
 * WordPress dependencies
 */
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Question block settings for multiple choice questions.
 *
 * @param {Object}   props
 * @param {Object}   props.options             Multiple choice question options.
 * @param {boolean}  props.options.randomOrder Display options in random order.
 * @param {Function} props.setOptions          Sets the options.
 */
const QuestionMultipleChoiceSettings = ( {
	options: { randomOrder = false },
	setOptions,
} ) => (
	<CheckboxControl
		label={ __( 'Random Order', 'sensei-lms' ) }
		checked={ randomOrder }
		onChange={ ( value ) => setOptions( { randomOrder: value } ) }
	/>
);

export default QuestionMultipleChoiceSettings;
