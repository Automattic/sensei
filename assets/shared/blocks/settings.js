import {
	ContrastChecker,
	InspectorControls,
	PanelColorSettings,
	withColors,
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { mapValues, upperFirst } from 'lodash';
import useThemeColors from '../../react-hooks/use-theme-colors';

/**
 * Default theme colors hook.
 *
 * @param {Object} colorSettings Color settings.
 * @param {Object} props         Current props.
 *
 * @return {Object} Color props override.
 */
const useDefaultThemeColors = ( colorSettings, props ) => {
	const themeColors = useThemeColors();
	const colorProps = {};

	Object.keys( colorSettings ).forEach( ( colorKey ) => {
		if ( props[ colorKey ]?.color ) {
			return;
		}

		const themeColor =
			themeColors[ colorSettings[ colorKey ].defaultThemeColorKey ];

		if ( themeColor ) {
			colorProps[ colorKey ] = {
				color: themeColor,
			};
		}
	} );

	return colorProps;
};

/**
 * Add color customization support and block settings controls for colors.
 *
 * @param {Object} colorSettings
 */
export const withColorSettings = ( colorSettings ) => {
	return ( Component ) => {
		const colors = mapValues(
			colorSettings,
			( settings ) => settings.style
		);

		const ComponentWithColorSettings = ( props ) => {
			const colorProps = useDefaultThemeColors( colorSettings, props );

			return (
				<>
					<Component { ...props } { ...colorProps } />
					<ColorSettings { ...{ colorSettings, props } } />
				</>
			);
		};

		return withColors( colors )( ComponentWithColorSettings );
	};
};

/**
 * Color setting inspector controls.
 *
 * @param {Object} params
 * @param {Object} params.colorSettings Color definitions.
 * @param {Object} params.props         Component props
 * @class
 */
export const ColorSettings = ( { colorSettings, props } ) => {
	const colors = Object.keys( colorSettings );
	return (
		<InspectorControls>
			<PanelColorSettings
				title={ __( 'Color settings', 'sensei-lms' ) }
				initialOpen={ false }
				colorSettings={ colors.map( ( color ) => ( {
					value: props[ color ].color,
					label: colorSettings[ color ].label,
					onChange: props[ `set${ upperFirst( color ) }` ],
				} ) ) }
			>
				{ props.backgroundColor && props.textColor && (
					<ContrastChecker
						{ ...{
							textColor: props.textColor.color,
							backgroundColor: props.backgroundColor.color,
						} }
						isLargeText={ false }
					/>
				) }
			</PanelColorSettings>
		</InspectorControls>
	);
};

/**
 * Apply default style class if no style is selected.
 * Adds is-style-default to the className property.
 */
export const withDefaultBlockStyle = () => ( Component ) => ( props ) => {
	let { className } = props;

	const extraProps = {};

	if ( ! className || ! className.includes( 'is-style-' ) ) {
		className = extraProps.className = [
			className,
			'is-style-default',
		].join( ' ' );
	}

	const style = className.match( /is-style-(\w+)/ );
	if ( style ) extraProps.blockStyle = style[ 1 ];

	return <Component { ...props } { ...extraProps } />;
};
