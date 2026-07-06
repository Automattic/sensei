/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { getBlockDefaultClassName } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
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
 * @param {{attributes}} props Block properties.
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
			colorProps.className,
			props?.attributes?.buttonClassName
		),
		style: {
			...borderProps.style,
			...colorProps.style,
		},
	};
}

/**
 * Class and style attributes for the wrapper element.
 *
 * Block API version 2+ no longer auto-injects the block's default
 * `wp-block-{name}` classname into `edit`/`save` props (that was a legacy
 * apiVersion <= 1 behavior); it must be added explicitly here via
 * `blockName`, see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-api-versions/README.md.
 *
 * @param {Object} props                  Block properties.
 * @param {string} props.className        Block classname (e.g. `is-style-*`).
 * @param {Object} props.attributes       Block attributes.
 * @param {string} props.attributes.align Alignment attribute.
 * @param {string} props.blockName        Registered block name.
 * @return {{className}} Output HTML attributes.
 */
export function getButtonWrapperProps( {
	className,
	attributes: { align },
	blockName,
} ) {
	return {
		className: classnames(
			blockName && getBlockDefaultClassName( blockName ),
			className,
			'wp-block-sensei-button',
			'wp-block-button',
			`has-text-align-${ align || 'left' }`
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
	/\bis-style-link\b/.test( props?.attributes?.className );
