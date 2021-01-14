import { RichText } from '@wordpress/block-editor';
import { getButtonProps, getButtonWrapperProps } from './button-props';
import classnames from 'classnames';

/**
 * Save function for a Button block.
 *
 * @param {Object} props
 * @param {Object} props.attributes Block attributes.
 * @param {string} props.className  Classname.
 * @param {string} props.tagName    Output HTML tag name.
 */
export const SaveButtonBlock = ( { attributes, className, tagName } ) => {
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
