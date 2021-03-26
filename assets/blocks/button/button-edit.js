/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getButtonProps, getButtonWrapperProps } from './button-props';
import ButtonSettings from './button-settings';

/**
 * Edit component for a Button block.
 *
 * @param {Object} props
 */
const ButtonEdit = ( props ) => {
	const { placeholder, attributes, setAttributes } = props;
	const { text } = attributes;
	const { colors } = useSelect( ( select ) => {
		return select( 'core/block-editor' ).getSettings();
	}, [] );

	const isReadonly = undefined !== props.text;
	const buttonProps = getButtonProps( { ...props, colors } );

	return (
		<div { ...getButtonWrapperProps( props ) }>
			{ isReadonly ? (
				<div { ...buttonProps }>{ props.text }</div>
			) : (
				<RichText
					placeholder={
						placeholder || __( 'Add text…', 'sensei-lms' )
					}
					value={ text }
					onChange={ ( value ) => setAttributes( { text: value } ) }
					{ ...buttonProps }
					tagName="a"
					identifier="text"
					withoutInteractiveFormatting={ true }
				/>
			) }
			<ButtonSettings { ...props } />
		</div>
	);
};

export default ButtonEdit;
