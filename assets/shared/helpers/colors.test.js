import { hexToRGB } from './colors';

describe( 'hexToRGB', () => {
	it.each( [
		[ 'full red', '#ff0000', 'rgb(255, 0, 0)' ],
		[ 'full green', '#00ff00', 'rgb(0, 255, 0)' ],
		[ 'full blue', '#0000ff', 'rgb(0, 0, 255)' ],
		[ 'black', '#000000', 'rgb(0, 0, 0)' ],
		[ 'white', '#ffffff', 'rgb(255, 255, 255)' ],
		[ 'invalid length', '#12345678', 'rgb(0, 0, 0)' ],
		[ 'invalid values', '#ffXXff', 'rgb(255, 0, 255)' ],
		[ 'already rgb', 'rgb(255, 0, 255)', 'rgb(255, 0, 255)' ],
		[ 'undefined', undefined, undefined ],
	] )(
		'hexToRGB returns rgb value when given a hex string of %s',
		( _, hex, rgbTriplet ) => {
			expect( hexToRGB( hex ) ).toEqual( rgbTriplet );
		}
	);
} );
