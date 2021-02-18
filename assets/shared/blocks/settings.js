/**
 * External dependencies
 */
import { mapValues, upperFirst } from 'lodash';

/**
 * WordPress dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import {
	ContrastChecker,
	InspectorControls,
	PanelColorSettings,
	withColors,
	getColorClassName,
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { useColorsByProbe } from '../../react-hooks/probe-styles';

/**
 * Add color customization support and block settings controls for colors.
 *
 * @param {Object} colorSettings
 */
export const withColorSettings = ( colorSettings ) => {
	return ( Component ) => {
		const ComponentWithColorSettings = ( props ) => (
			<>
				<Component { ...props } />
				<ColorSettings { ...{ colorSettings, props } } />
			</>
		);

		const colors = mapValues(
			colorSettings,
			( settings ) => settings.style
		);

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
					onChange: ( newColor ) => {
						props[ `set${ upperFirst( color ) }` ]( newColor );

						if ( colorSettings[ color ].onChange )
							colorSettings[ color ].onChange( {
								...props,
								colorValue: newColor,
							} );
					},
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
 *
 * @param {string} defaultStyleName Default style name.
 */
export const withDefaultBlockStyle = ( defaultStyleName = 'default' ) => (
	Component
) => ( props ) => {
	let { className } = props;

	const extraProps = {};

	if ( ! className || ! className.includes( 'is-style-' ) ) {
		className = extraProps.className = [
			className,
			`is-style-${ defaultStyleName }`,
		].join( ' ' );
	}

	const style = className.match( /is-style-(\w+)/ );
	if ( style ) extraProps.blockStyle = style[ 1 ];

	return <Component { ...props } { ...extraProps } />;
};

/**
 * This HOC sets the default color attribute based in a probe.
 *
 * @example
 * withDefaultColor( {
 *   defaultMainColor: {
 *     style: 'background-color',
 *     probeKey: 'primaryColor',
 *   },
 * } )
 *
 * @param {Object} colorConfigs Colors config object, where the key is the
 *                              default color attribute name, and the value is
 *                              an object containing style type and probeKey.
 *                              The block attributes must register an attribute
 *                              for every key.
 *
 * @return {Function} Extended component.
 */
export const withDefaultColor = ( colorConfigs ) => ( Component ) => (
	props
) => {
	const { setAttributes, attributes } = props;
	const colorsByProbe = useColorsByProbe();
	const [ colorProps, setColorProps ] = useState( {} );

	const colorConfigsDeps = Object.keys( colorConfigs ).map(
		( colorKey ) => attributes[ colorKey ]
	);

	useEffect( () => {
		const newColorProps = {};

		Object.entries( colorConfigs ).forEach(
			( [ colorKey, { style, probeKey } ] ) => {
				const probeColor = colorsByProbe[ probeKey ] || {};
				const { slug } = probeColor;

				if ( slug ) {
					newColorProps[ colorKey ] = {
						...probeColor,
						className: getColorClassName( style, slug ),
					};
				}

				if ( attributes[ colorKey ] !== slug ) {
					const colorAttributes = {};
					colorAttributes[ colorKey ] = slug;

					// Border color is not compatible with all themes as className, so the color value is needed.
					if ( 'border-color' === style ) {
						colorAttributes[ `${ colorKey }Value` ] =
							probeColor.color;
					}

					setAttributes( colorAttributes );
				}
			}
		);

		setColorProps( newColorProps );

		// eslint-disable-next-line react-hooks/exhaustive-deps -- The deps are added dynamically because we get it dynamically from the attributes and we don't want add all attributes as dep.
	}, [ colorsByProbe, setAttributes, ...colorConfigsDeps ] );

	return <Component { ...props } { ...colorProps } />;
};
