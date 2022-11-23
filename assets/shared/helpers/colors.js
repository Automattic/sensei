/**
 * Converts css color hex to rgb.
 *
 * @param {string} h The hex color string.
 *
 * @return {string} The rgb value.
 */
export const hexToRGB = ( h ) => {
	// Returns if it's not an hexadecimal.
	if ( ! h || null === h.match( '#' ) ) {
		return h;
	}

	let r = 0,
		g = 0,
		b = 0;

	const hexCode =
		4 === h.length
			? `#${ h[ 1 ] + h[ 1 ] + h[ 2 ] + h[ 2 ] + h[ 3 ] + h[ 3 ] }`
			: h;

	if ( 7 === hexCode.length ) {
		r = parseInt( hexCode.substr( 1, 2 ), 16 ) || 0;
		g = parseInt( hexCode.substr( 3, 2 ), 16 ) || 0;
		b = parseInt( hexCode.substr( 5, 2 ), 16 ) || 0;
	}

	return `rgb(${ r }, ${ g }, ${ b })`;
};
