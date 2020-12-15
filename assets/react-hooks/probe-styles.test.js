import { getProbeStyles } from './probe-styles';

describe( 'getProbeStyles', () => {
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
} );
