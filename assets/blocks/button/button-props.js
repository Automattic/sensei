import classnames from 'classnames';


/**
 * Class and style attributes for the button.
 *
 * @param {Object} props Block properties.
 * @return {{className, style}} Output HTML attributes.
 */
export function getButtonProps( props ) {
	return {
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
