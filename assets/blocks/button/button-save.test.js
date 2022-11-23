/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ButtonSave from './button-save';

describe( 'ButtonSave', () => {
	it( 'sets wrapper class and default alignment', () => {
		const { container } = render(
			ButtonSave( {
				attributes: { text: 'Button' },
			} )
		);

		const { classList } = container.firstChild;
		expect( classList ).toContain( 'has-text-align-left' );
		expect( classList ).toContain( 'wp-block-sensei-button' );
	} );

	it( 'sets wrapper alignment from attribute', () => {
		const { container } = render(
			ButtonSave( {
				attributes: { text: 'Button', align: 'center' },
			} )
		);

		const { classList } = container.firstChild;
		expect( classList ).toContain( 'has-text-align-center' );
	} );

	it( 'renders content as tagName', () => {
		const { container } = render(
			ButtonSave( {
				tagName: 'button',
				attributes: { text: 'Button' },
			} )
		);

		const button = container.getElementsByTagName( 'button' );
		expect( button.length ).toEqual( 1 );
		expect( button[ 0 ].textContent ).toEqual( 'Button' );
	} );
} );
