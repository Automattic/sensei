import { useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	ContrastChecker,
	InspectorControls,
	PanelColorSettings,
	withColors,
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { mapValues, upperFirst } from 'lodash';

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
 * Hook to set the default style if no style is defined.
 *
 * @param {string} clientId     Block client ID.
 * @param {string} defaultStyle Default block style.
 */
export const useSetDefaultStyle = ( clientId, defaultStyle ) => {
	const attributesSelector = ( select ) =>
		select( 'core/block-editor' ).getBlock( clientId ).attributes;

	const { className = '' } = useSelect( attributesSelector, [ clientId ] );
	const { updateBlockAttributes } = useDispatch( 'core/block-editor' );

	useEffect( () => {
		if ( ! className.match( /is-style-\w+/ ) && defaultStyle ) {
			updateBlockAttributes( clientId, {
				className: `${ className } is-style-${ defaultStyle }`,
			} );
		}
	}, [ clientId, className, defaultStyle, updateBlockAttributes ] );
};
