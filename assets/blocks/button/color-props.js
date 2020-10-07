/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import {
	getColorClassName,
	getColorObjectByAttributeValues,
} from '@wordpress/block-editor';

/**
 * Get className for gradient.
 *
 * @param {string} gradientSlug
 * @return {string|undefined} Class.
 */
export const getGradientClass = ( gradientSlug ) => {
	if ( ! gradientSlug ) {
		return undefined;
	}
	return `has-${ gradientSlug }-gradient-background`;
};

/**
 * Get className and style from color settings.
 *
 * Copied from Gutenberg color support hook.
 *
 * @param {Object} props
 * @param {Object} props.attributes Block Attributes.
 * @param {Object} props.colors     Theme color settings.
 */
export const getColorAndStyleProps = ( { attributes, colors } ) => {
	const {
		backgroundColor,
		customBackgroundColor,
		textColor,
		customTextColor,
		gradient,
		style = {},
	} = attributes;

	const backgroundClass = getColorClassName(
		'background-color',
		backgroundColor
	);
	if ( ! style.color ) style.color = {};
	if ( customBackgroundColor ) style.color.background = customBackgroundColor;
	if ( customTextColor ) style.color.text = customTextColor;

	const gradientClass = getGradientClass( gradient );
	const textClass = getColorClassName( 'color', textColor );
	const className = classnames( textClass, gradientClass, {
		// Don't apply the background class if there's a custom gradient
		[ backgroundClass ]: ! style?.color?.gradient && !! backgroundClass,
		'has-text-color': textColor || style?.color?.text,
		'has-background':
			backgroundColor ||
			style?.color?.background ||
			gradient ||
			style?.color?.gradient,
	} );
	const styleProp =
		style?.color?.background || style?.color?.text || style?.color?.gradient
			? {
					background: style?.color?.gradient
						? style.color.gradient
						: undefined,
					backgroundColor: style?.color?.background
						? style.color.background
						: undefined,
					color: style?.color?.text ? style.color.text : undefined,
			  }
			: {};

	// This is needed only for themes that don't load their color stylesheets in the editor
	// We force an inline style to apply the color.
	if ( colors ) {
		if ( backgroundColor ) {
			const backgroundColorObject = getColorObjectByAttributeValues(
				colors,
				backgroundColor
			);

			styleProp.backgroundColor = backgroundColorObject.color;
		}

		if ( textColor ) {
			const textColorObject = getColorObjectByAttributeValues(
				colors,
				textColor
			);

			styleProp.color = textColorObject.color;
		}
	}

	return {
		className: !! className ? className : undefined,
		style: styleProp,
	};
};
