/**
 * Round a number with certain amount of decimal digits.
 *
 * @param {number} number The number to be rounded.
 * @param {number} digits The number of digits to appear after the decimal point.
 *
 * @return {number} Rounded number.
 */
const roundWithDecimals = ( number, digits ) => {
	const multiplier = Math.pow( 10, digits );

	return Math.round( ( number + Number.EPSILON ) * multiplier ) / multiplier;
};

export default roundWithDecimals;
