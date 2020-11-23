import { memoize } from 'lodash';

const { getComputedStyle } = window;

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
