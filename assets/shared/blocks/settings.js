/**
 * Build PanelColorSettings configuration.
 *
 * @param {string}   name                Style attribute name.
 * @param {string}   label               Control label.
 * @param {Object}   props               Component props.
 * @param {Object}   props.style
 * @param {Function} props.setAttributes
 * @return {Object} Configuration.
 */
export const colorSetting = ( name, label, { style, setAttributes } ) => {
	return {
		value: style[ name ],
		onChange: ( colorValue ) =>
			setAttributes( {
				style: {
					...style,
					[ name ]: colorValue,
				},
			} ),
		label,
	};
};
