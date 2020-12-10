import { getButtonProps } from './button-props';

describe( 'getButtonProps', () => {
	it( 'adds named color classes', () => {
		const { className } = getButtonProps( {
			attributes: {
				textColor: 'primary',
				backgroundColor: 'secondary',
			},
		} );

		expect( className ).toMatch( /\bhas-background\b/ );
		expect( className ).toMatch( /\bhas-secondary-background-color\b/ );
		expect( className ).toMatch( /\bhas-text-color\b/ );
		expect( className ).toMatch( /\bhas-primary-color\b/ );
	} );

	it( 'adds inline styles from withColors hook attributes', () => {
		const { style } = getButtonProps( {
			attributes: {
				customTextColor: '#333',
				customBackgroundColor: '#eee',
			},
		} );

		expect( style.color ).toEqual( '#333' );
		expect( style.backgroundColor ).toEqual( '#eee' );
	} );

	it( 'adds inline styles from color support hook attributes', () => {
		const { style } = getButtonProps( {
			attributes: {
				style: {
					color: {
						text: '#333',
						background: '#eee',
						gradient: 'linear-gradient()',
					},
				},
			},
		} );

		expect( style.color ).toEqual( '#333' );
		expect( style.backgroundColor ).toEqual( '#eee' );
		expect( style.background ).toEqual( 'linear-gradient()' );
	} );

	it( 'adds inline style for border radius', () => {
		const { style } = getButtonProps( {
			attributes: {
				borderRadius: 5,
			},
		} );

		expect( style.borderRadius ).toEqual( '5px' );
	} );
} );
