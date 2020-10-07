import classnames from 'classnames';

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
	const borderProps = getBorderRadiusProps( props );
	return {
		className: classnames( 'wp-block-button__link', borderProps.className ),
		style: {
			...borderProps.style,
		},
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
