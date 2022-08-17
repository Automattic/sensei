/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * External dependencies
 */
import { compact } from 'lodash';
/**
 * Internal dependencies
 */

/**
 * useColors hook.
 *
 * This hook encapsulate the logic to update attributes when colors are updated.
 * It is heavily dependent of the Gutenberg with-colors.
 *
 * @param {Object}   props                        Hook Props.
 * @param {Function} props.setBackgroundColor     Set the background color. It is created by Gutenberg with-colors hook.
 * @param {Function} props.setTextColor           Set the category text color. It is created by Gutenberg with-colors hook.
 * @param {Function} props.attributes             Block attributes.
 * @param {Function} props.setAttributes          Update the block attributes to be stored during the save.
 * @param {Function} props.textColor              Selected category Text Color Object enhanced by Gutenberg with-colors hook.
 * @param {Function} props.backgroundColor        Selected category Background Color Object enhanced by Gutenberg with-colors hook.
 * @param {Function} props.defaultTextColor       Default category Text Color loaded from the theme.
 * @param {Function} props.defaultBackgroundColor Default category Background loaded from the theme
 * @return {Object}  Object containing the textColor, backgroundColor and the setter to update them.
 */
const useColors = ( {
	setBackgroundColor,
	setTextColor,
	attributes,
	setAttributes,
	textColor,
	backgroundColor,
	defaultBackgroundColor,
	defaultTextColor,
} ) => {
	// It set the block saved colors
	const { categoryStyle } = attributes;

	// It set the internal colors state using the colors incoming from a saved block or the theme colors.
	useEffect( () => {
		setTextColor( categoryStyle?.style.color || defaultTextColor?.color );
		setBackgroundColor(
			categoryStyle?.style.backgroundColor ||
				defaultBackgroundColor?.color
		);
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ defaultBackgroundColor, defaultTextColor ] );

	// It updates the block attributes with the new colors already parsed by with-colors hook.
	useEffect( () => {
		setAttributes( {
			...attributes,
			categoryStyle: {
				classes: compact( [
					textColor?.class,
					backgroundColor?.class,
				] ),
				style: {
					color: textColor?.color,
					backgroundColor: backgroundColor?.color,
				},
			},
		} );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ textColor, backgroundColor ] );

	return {
		setTextColor,
		setBackgroundColor,
		textColor: textColor || null,
		backgroundColor: backgroundColor || null,
	};
};

export default useColors;
