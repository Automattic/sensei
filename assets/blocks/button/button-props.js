import classnames from 'classnames';
import { getColorAndStyleProps } from './color-props';

/**
 * Class and style attributes for border radius.
 *
 * @param {Object} props
 * @param {Object} props.attributes
 * @param {number} props.attributes.borderRadius Border radius attribute.
 * @return {{className, style}} Output HTML attributes.
 */
export function getBorderRadiusProps( { attributes: { borderRadius } } ) {
	return {
		className: {
			'no-border-radius': borderRadius === 0,
		},
		style: {
			borderRadius: borderRadius ? borderRadius + 'px' : undefined,
		},
	};
}

/**
 * Class and style attributes for the button.
 *
 * @param {{attributes, tagName}} props Block properties.
 * @return {{className, style}} Output HTML attributes.
 */
export function getButtonProps( props ) {
	const isLink = isLinkStyle( props );

	const colorProps = getColorAndStyleProps( props );
	const borderProps = getBorderRadiusProps( props );

	return {
		className: classnames(
			{ 'wp-block-button__link': ! isLink },
			borderProps.className,
			colorProps.className
		),
		style: {
			...borderProps.style,
			...colorProps.style,
		},
		tagName: props.tagName,
	};
}

/**
 * Class and style attributes for the wrapper element.
 *
 * @param {Object} props                  Block properties.
 * @param {string} props.className        Block classname.
 * @param {Object} props.attributes       Block attributes.
 * @param {string} props.attributes.align Alignment attribute.
 * @return {{className}} Output HTML attributes.
 */
export function getButtonWrapperProps( { className, attributes: { align } } ) {
	return {
		className: classnames(
			className,
			'wp-block-sensei-button',
			`has-text-align-${ align || 'full' }`
		),
	};
}

/**
 * Check if block has the 'Link' block style.
 *
 * @param {Object} props Block props.
 * @return {boolean} Is it a link block style.
 */
export const isLinkStyle = ( props ) =>
	props?.attributes?.className?.test( /\bis-style-link\b/ );
