import { RichText } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { getButtonProps, getButtonWrapperProps } from './button-props';
import { ButtonBlockSettings } from './settings-button';

/**
 * Edit component for a Button block.
 *
 * @param {Object} props
 */
export const EditButtonBlock = ( props ) => {
	const { attributes, setAttributes } = props;
	const { placeholder, text } = attributes;
	const { colors } = useSelect( ( select ) => {
		return select( 'core/block-editor' ).getSettings();
	}, [] );
	return (
		<div { ...getButtonWrapperProps( props ) }>
			<RichText
				placeholder={ placeholder || __( 'Add text…', 'sensei-lms' ) }
				value={ text }
				onChange={ ( value ) => setAttributes( { text: value } ) }
				withoutInteractiveFormatting
				{ ...getButtonProps( { attributes, colors } ) }
				identifier="text"
			/>
			<ButtonBlockSettings { ...props } />
		</div>
	);
};
