import { RichText } from '@wordpress/block-editor';
import { getBlockDefaultClassName } from '@wordpress/blocks';
import classnames from 'classnames';

import { getButtonProps, getButtonWrapperProps } from './button-props';

/**
 * Save function for a Button block.
 *
 * @param {Object} props
 * @param {Object} props.attributes Block attributes.
 * @param {string} props.className  Classname.
 * @param {string} props.tagName    Output HTML tag name.
 * @param {string} props.blockName  Block name.
 */
export const SaveButtonBlock = ( {
	attributes,
	className,
	tagName,
	blockName,
} ) => {
	const { text, inContainer, align } = attributes;

	const content = (
		<div { ...getButtonWrapperProps( { className, attributes } ) }>
			<RichText.Content
				{ ...getButtonProps( { attributes } ) }
				tagName={ tagName }
				value={ text }
			/>
		</div>
	);

	if ( inContainer ) {
		return (
			<div
				className={ classnames(
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
