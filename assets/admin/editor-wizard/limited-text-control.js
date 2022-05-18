/**
 * WordPress dependencies
 */
import { TextareaControl, TextControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

const LimitedTextControl = ( {
	label,
	value,
	onChange,
	maxLength,
	multiline = false,
} ) => {
	const changeValueIfAllowed = ( newValue ) => {
		if ( newValue.length <= maxLength ) {
			onChange( newValue );
		}
	};

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
			onChange={ changeValueIfAllowed }
			value={ value }
		/>
	);
};

export default LimitedTextControl;
