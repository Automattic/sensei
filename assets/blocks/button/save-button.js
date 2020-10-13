import { RichText } from '@wordpress/block-editor';
import { getButtonProps, getButtonWrapperProps } from './button-props';

/**
 * Save function for a Button block.
 *
 * @param {Object} props
 * @param {Object} props.attributes Block attributes.
 * @param {string} props.className  Classname.
 * @param {string} props.tagName    Output HTML tag name.
 */
export const saveButtonBlock = ( { attributes, className, tagName } ) => {
	const { text } = attributes;

	return (
		<div { ...getButtonWrapperProps( { className, attributes } ) }>
			<RichText.Content
				tagName={ tagName }
				{ ...getButtonProps( { attributes } ) }
				value={ text }
			/>
		</div>
	);
};
