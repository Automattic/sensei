/**
 * Internal dependencies
 */
import { getProbeStyles } from './probe-styles';

describe( 'getProbeStyles', () => {
	afterEach( () => {
		// getProbeStyles is memoized; reset between cases.
		getProbeStyles.cache.clear();
		document
			.querySelectorAll( 'style, iframe[name="editor-canvas"]' )
			.forEach( ( node ) => node.remove() );
	} );

	it( 'Should get the probe styles', () => {
		const styles = `.wp-block-button__link {
			background-color: rgb(0, 0, 0);
			color: rgb(255, 255, 255);
		}`;

		const style = document.createElement( 'style' );
		style.appendChild( document.createTextNode( styles ) );

		document.head.appendChild( style );

		const probeStyles = getProbeStyles();

		expect( probeStyles.primaryColor ).toEqual( 'rgb(0, 0, 0)' );
		expect( probeStyles.primaryContrastColor ).toEqual(
			'rgb(255, 255, 255)'
		);
	} );

	it( 'Should probe the editor canvas iframe when the post editor is iframed', () => {
		// Outer document deliberately styles the probe a different color, so a
		// wrong result would prove the probe read the outer document.
		const outerStyle = document.createElement( 'style' );
		outerStyle.appendChild(
			document.createTextNode( `.wp-block-button__link {
				background-color: rgb(1, 1, 1);
				color: rgb(2, 2, 2);
			}` )
		);
		document.head.appendChild( outerStyle );

		const iframe = document.createElement( 'iframe' );
		iframe.name = 'editor-canvas';
		document.body.appendChild( iframe );

		iframe.contentDocument.head.innerHTML = `<style>.wp-block-button__link {
			background-color: rgb(17, 17, 17);
			color: rgb(249, 249, 249);
		}</style>`;

		const probeStyles = getProbeStyles();

		expect( probeStyles.primaryColor ).toEqual( 'rgb(17, 17, 17)' );
		expect( probeStyles.primaryContrastColor ).toEqual(
			'rgb(249, 249, 249)'
		);
	} );
} );
