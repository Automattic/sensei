/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Add settings to generate CSS variables for colors in the edit and save wrappers.
 *
 * @param {Object} settings Block settings.
 */
function withColorVariableSupport( settings ) {
	const { getEditWrapperProps: baseGetEditWrapperProps } = settings;

	const getEditWrapperProps = ( attributes ) => {
		let props = {};
		if ( baseGetEditWrapperProps ) {
			props = baseGetEditWrapperProps( attributes );
		}
		const style = getCSSVariables( attributes );
		return {
			...props,
			style: {
				...( props.style || {} ),
				...( style || {} ),
			},
		};
	};

	return {
		...settings,
		getEditWrapperProps,
	};
}

/**
 * Get CSS variables for colors from style and color attributes.
 *
 * @param {Object} attributes Block attributes.
 * @return {Object} CSS variable name-value pairs.
 */
function getCSSVariables( attributes ) {
	const { style, backgroundColor, customBackgroundColor } = attributes;
	const link = style?.elements?.link?.color?.text;
	const background =
		compileStyleValue( style?.color?.background ) ||
		compileNamed( backgroundColor ) ||
		customBackgroundColor;

	const vars = {};
	if ( link ) {
		vars[ '--sensei-primary-color' ] = compileStyleValue( link );
	}
	if ( background ) {
		vars[ '--sensei-background-color' ] = background;
		vars[ '--sensei-primary-contrast-color' ] = background;
	}
	return vars;
}

/**
 * Turn variable reference to CSS.
 *
 * From @wordpress/edit-site/src/components/global-styles/use-global-styles-output.js
 *
 * @param {string} uncompiledValue
 * @return {string} CSS property value.
 */
function compileStyleValue( uncompiledValue ) {
	const VARIABLE_REFERENCE_PREFIX = 'var:';
	const VARIABLE_PATH_SEPARATOR_TOKEN_ATTRIBUTE = '|';
	const VARIABLE_PATH_SEPARATOR_TOKEN_STYLE = '--';

	if ( uncompiledValue?.startsWith?.( VARIABLE_REFERENCE_PREFIX ) ) {
		const variable = uncompiledValue
			.slice( VARIABLE_REFERENCE_PREFIX.length )
			.split( VARIABLE_PATH_SEPARATOR_TOKEN_ATTRIBUTE )
			.join( VARIABLE_PATH_SEPARATOR_TOKEN_STYLE );
		return `var(--wp--${ variable })`;
	}
	return uncompiledValue;
}

/**
 * Turn color name to CSS.
 *
 * @param {string} value A color name.
 * @return {string} CSS property value.
 */
function compileNamed( value ) {
	return value && `var(--wp--preset--color--${ value })`;
}

addFilter(
	'blocks.registerBlockType',
	'sensei/supports-color/withColorVariableSupport',
	withColorVariableSupport
);
