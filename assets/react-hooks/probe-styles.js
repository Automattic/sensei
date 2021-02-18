/**
 * External dependencies
 */
import { mapValues, keyBy, memoize } from 'lodash';

/**
 * WordPress dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { hexToRGB } from '../shared/helpers/colors';

const { getComputedStyle } = window;

/**
 * Get color object by probe.
 *
 * @return {Object} Object containing the color objects, where the key is the probe key.
 */
export const useColorsByProbe = () => {
	const themeColorPalette = useSelect(
		( select ) => select( 'core/editor' ).getEditorSettings().colors,
		[]
	);
	const [ colorSlugsByProbe, setColorSlugsByProbe ] = useState( {} );

	useEffect( () => {
		const probeStyles = getProbeStyles();
		const newState = {};
		const slugsByColor = mapValues(
			keyBy( themeColorPalette, ( item ) => hexToRGB( item.color ) ),
			'slug'
		);

		Object.entries( probeStyles ).forEach( ( [ key, color ] ) => {
			const slug = slugsByColor[ hexToRGB( color ) ];

			if ( slug ) {
				newState[ key ] = { slug, color };
			}
		} );

		setColorSlugsByProbe( newState );
	}, [ themeColorPalette ] );

	return colorSlugsByProbe;
};

/**
 * Get probe styles (memoized).
 *
 * It adds elements to the DOM as a probe, and get the computed styles
 * the default expected properties.
 *
 * @return {Object} Probe default styles.
 */
export const getProbeStyles = memoize( () => {
	// Create temporary probe elements.
	const editorStylesWrapperDiv = document.createElement( 'div' );
	editorStylesWrapperDiv.className =
		'editor-styles-wrapper sensei-probe-element';

	const blockButtonDiv = document.createElement( 'div' );
	blockButtonDiv.className = 'wp-block-button';

	const buttonLinkDiv = document.createElement( 'div' );
	buttonLinkDiv.className = 'wp-block-button__link';
	buttonLinkDiv.textContent = 'Probe';

	// Set probe position outside the screen to be hidden.
	editorStylesWrapperDiv.style.position = 'fixed';
	editorStylesWrapperDiv.style.top = '-100vh';

	// Add probe to the screen.
	blockButtonDiv.appendChild( buttonLinkDiv );
	editorStylesWrapperDiv.appendChild( blockButtonDiv );
	document.body.appendChild( editorStylesWrapperDiv );

	// Save styles.
	const styles = {
		primaryColor: getComputedStyle( buttonLinkDiv ).backgroundColor,
		primaryContrastColor: getComputedStyle( buttonLinkDiv ).color,
	};

	// Remove probe.
	document.body.removeChild( editorStylesWrapperDiv );

	return styles;
} );
