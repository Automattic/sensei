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
 * @param {Object} props Block properties.
 * @return {{className, style}} Output HTML attributes.
 */
export function getButtonProps( props ) {
	const colorProps = getColorAndStyleProps( props );
	const borderProps = getBorderRadiusProps( props );
	return {
		className: classnames(
			'wp-block-button__link',
			borderProps.className,
			colorProps.className
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
 * @param {Object} props                       Block properties.
 * @param {string} props.className             Block classname.
 * @param {Object} props.attributes            Block attributes.
 * @param {string} props.attributes.blockAlign Block alignment attribute.
 * @param {string} props.attributes.textAlign  Text alignment attribute.
 *
 * @return {{className}} Output HTML attributes.
 */
export function getButtonWrapperProps( {
	className,
	attributes: { blockAlign, textAlign },
} ) {
	const wrapperProps = {
		className: classnames( className, 'wp-block-sensei-button', {
			[ `wp-block-sensei-button__block-align-${ blockAlign }` ]: blockAlign,
			alignfull: 'full' === blockAlign,
			[ `wp-block-sensei-button__text-align-${ textAlign }` ]: textAlign,
		} ),
	};

	return wrapperProps;
}

/**
 * Block additional wrapper props.
 *
 * @param {Object} attributes            Block attributes.
 * @param {string} attributes.blockAlign Block align.
 *
 * @return {Object} Additional wrapper props.
 */
export function getEditWrapperProps( { blockAlign } ) {
	const props = {};

	if ( 'full' === blockAlign ) {
		props[ 'data-align' ] = blockAlign;
	}

	return props;
}
