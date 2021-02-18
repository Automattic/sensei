/**
 * External dependencies
 */
import interpolateComponents from 'interpolate-components';

export const formattingComponents = {
	em: <em />,
	strong: <strong />,
	code: <code />,
	small: <small />,
	sub: <sub />,
	sup: <sup />,
	br: <br />,
	p: <p />,
	del: <del />,
};

/**
 * Interpolate components and create a node from the given template string.
 *
 * @example formatString(' Welcome to {{strong}}Sensei{{/strong}}!')
 *
 * @param {string} mixedString Template string.
 * @param {Object} components  Replacements.
 */
export const formatString = ( mixedString, components = {} ) =>
	interpolateComponents( {
		mixedString,
		components: { ...formattingComponents, ...components },
	} );
