/**
 * WordPress dependencies
 */
import { TextareaControl, TextControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Text control with input limited by a given maximum length.
 *
 * @param {Object}   props           Component properties.
 * @param {string}   props.label     Label for the field.
 * @param {string}   props.value     Value for the field.
 * @param {Function} props.onChange  Callback for when the value changes.
 * @param {number}   props.maxLength Maximum length for the field.
 * @param {boolean}  props.multiline Whether if multiline input (textarea) must be used or not.
 */
const LimitedTextControl = ( {
	label,
	value,
	onChange,
	maxLength,
	multiline = false,
} ) => {
	const Control = multiline ? TextareaControl : TextControl;

	return (
		<Control
			help={ sprintf(
				// translators: %1$d number of characters introduced, %2$d number of total characters allowed.
				__( 'Characters: %1$d/%2$d', 'sensei-lms' ),
				value.length,
				maxLength
			) }
			label={ label }
			onChange={ onChange }
			value={ value }
			maxLength={ maxLength }
		/>
	);
};

export default LimitedTextControl;
