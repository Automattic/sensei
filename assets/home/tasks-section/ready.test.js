/**
 * External dependencies
 */
import { render, fireEvent, waitFor } from '@testing-library/react';
import nock from 'nock';

/**
 * Internal dependencies
 */
import Ready from './ready';

describe( '<Ready />', () => {
	it( 'Should call dismiss callback when clicking on dismiss', async () => {
		const onDismissMock = jest.fn();

		window.sensei_home = {
			dismiss_tasks_nonce: 'nonce',
		};
		window.ajaxurl = '/';

		nock( 'http://localhost' ).post( '/' ).reply( 200, {} );

		const { container } = render( <Ready onDismiss={ onDismissMock } /> );

		fireEvent.click(
			container.querySelector( '.sensei-home-ready__dismiss' )
		);

		await waitFor( () => {
			expect( onDismissMock ).toBeCalled();
		} );
	} );
} );
