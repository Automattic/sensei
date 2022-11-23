/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { RichText } from '@wordpress/block-editor';
import { getBlockDefaultClassName } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import {
	getButtonProps,
	getButtonWrapperProps,
	isLinkStyle,
} from './button-props';

/**
 * Save function for a Button block.
 *
 * @param {Object} props
 * @param {Object} props.attributes Block attributes.
 * @param {string} props.className  Classname.
 * @param {string} props.tagName    Output HTML tag name.
 * @param {string} props.blockName  Block name.
 */
const ButtonSave = ( { attributes, className, tagName, blockName } ) => {
	const { text, inContainer, align } = attributes;

	let buttonTagName = tagName;

	if ( ! tagName ) {
		buttonTagName = isLinkStyle( { attributes } ) ? 'a' : 'button';
	}

	const content = (
		<div { ...getButtonWrapperProps( { className, attributes } ) }>
			<RichText.Content
				{ ...getButtonProps( { attributes } ) }
				tagName={ buttonTagName }
				value={ text }
			/>
		</div>
	);

	if ( inContainer ) {
		return (
			<div
				className={ classnames(
					className,
					'sensei-buttons-container__button-block',
					getBlockDefaultClassName( blockName ) + '__wrapper',
					{
						[ `sensei-buttons-container__button-align-${ align }` ]: align,
					}
				) }
			>
				{ content }
			</div>
		);
	}

	return content;
};

export default ButtonSave;
