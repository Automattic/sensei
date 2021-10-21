/**
 * WordPress dependencies
 */
import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';

/**
 * Question block settings for multiple choice questions.
 *
 * @param {Object}   props
 * @param {Object}   props.options             Multiple choice question options.
 * @param {boolean}  props.options.randomOrder Display options in random order.
 * @param {Function} props.setOptions          Sets the options.
 */
const QuestionMultipleChoiceSettings = ( {
	options: { randomOrder },
	setOptions,
} ) => {
	// randomOrder is a specific option for the multiple choice question.
	// We can't add it to the block.json as it applies for all blocks.
	useEffect( () => {
		if ( undefined === randomOrder ) {
			setOptions( { randomOrder: true } );
		}
	}, [ randomOrder, setOptions ] );

	return (
		<CheckboxControl
			label={ __( 'Random Order', 'sensei-lms' ) }
			checked={ randomOrder }
			onChange={ ( value ) => setOptions( { randomOrder: value } ) }
		/>
	);
};

export default QuestionMultipleChoiceSettings;
