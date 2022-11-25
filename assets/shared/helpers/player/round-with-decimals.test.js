/**
 * Internal dependencies
 */
import roundWithDecimals from './round-with-decimals';

describe( 'roundWithDecimals', () => {
	it( 'Should round with 3 digits to down when down is the nearest', () => {
		const roundedNumber = roundWithDecimals( 10.1234, 3 );

		expect( roundedNumber ).toEqual( 10.123 );
	} );

	it( 'Should round with 3 digits to up when up is the nearest', () => {
		const roundedNumber = roundWithDecimals( 10.1236, 3 );

		expect( roundedNumber ).toEqual( 10.124 );
	} );

	it( 'Should round with 1 digit', () => {
		const roundedNumber = roundWithDecimals( 10.1234, 1 );

		expect( roundedNumber ).toEqual( 10.1 );
	} );

	it( 'Should round with 0 digits', () => {
		const roundedNumber = roundWithDecimals( 10.1234, 0 );

		expect( roundedNumber ).toEqual( 10 );
	} );
} );
