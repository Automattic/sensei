import { act, fireEvent, render } from '@testing-library/react';
import { SenseiExtensionInstall, withInstaller } from './extensions.component';
import apiFetch from '@wordpress/api-fetch';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

describe( '<SenseiExtensionInstall />', () => {
	it( 'has install button', () => {
		const onInstall = jest.fn();
		const { getByRole } = render(
			<SenseiExtensionInstall status={ '' } install={ onInstall } />
		);
		fireEvent.click( getByRole( 'button', { name: 'Install' } ) );
		expect( onInstall ).toHaveBeenCalled();
	} );

	it( 'shows installing status', () => {
		const { getByRole } = render(
			<SenseiExtensionInstall status={ 'installing' } />
		);

		expect( getByRole( 'button', { text: 'Installing..' } ) ).toBeTruthy();
		expect( getByRole( 'button' ).disabled ).toBeTruthy();
	} );

	it( 'shows installed status', () => {
		const { getByRole } = render(
			<SenseiExtensionInstall status={ 'installed' } />
		);

		expect( getByRole( 'button', { text: 'Installed.' } ) ).toBeTruthy();
		expect( getByRole( 'button' ).disabled ).toBeTruthy();
	} );

	it( 'shows error', () => {
		const errorDiv = document.createElement( 'div' );
		const { getByRole } = render(
			<SenseiExtensionInstall
				error="Error message"
				errorContainer={ errorDiv }
			/>
		);

		expect( getByRole( 'button', { text: 'Install' } ) ).toBeTruthy();
		expect( getByRole( 'button' ).disabled ).toBeFalsy();
		expect( errorDiv.textContent ).toEqual( 'Error message' );
	} );
} );

describe( 'extensions.js', () => {
	it( 'attaches React component', () => {
		document.body.innerHTML =
			'<div class="sensei-extension-installer" data-slug="slug" data-source="wporg"></div>';
		require( './extensions' );

		expect( document.body.innerHTML ).toContain( 'Install' );
	} );
} );

describe( 'withInstaller', () => {
	let component;
	let Installer;
	beforeEach( () => {
		Installer = withInstaller( ( props ) => {
			component = props;
			return <div />;
		} );
	} );
	describe( 'Wordpress.org plugin', () => {
		it( 'starts installation', async () => {
			apiFetch.mockResolvedValue( { status: 'installed' } );

			render( <Installer slug="test" source="wporg" /> );
			await act( async () => {
				await component.install();
			} );

			expect( apiFetch ).toHaveBeenCalledWith( {
				data: { slug: 'test' },
				method: 'POST',
				path: '/sensei-internal/v1/extensions/extensions-install',
			} );
		} );

		it( 'polls for updates', async () => {
			jest.useFakeTimers();
			apiFetch.mockResolvedValueOnce( { status: 'installing' } );

			render( <Installer slug="test" source="wporg" /> );
			await act( async () => {
				await component.install();
			} );

			apiFetch.mockClear();

			expect( component.status ).toEqual( 'installing' );

			apiFetch.mockResolvedValueOnce( { status: 'installed' } );

			await act( async () => {
				await jest.runOnlyPendingTimers();
				await Promise.resolve();
			} );

			expect( apiFetch ).toHaveBeenCalledWith( {
				method: 'GET',
				path:
					'/sensei-internal/v1/extensions/extensions-install?slug=test',
			} );

			expect( component.status ).toEqual( 'installed' );
		} );

		it( 'handles errors', async () => {
			apiFetch.mockResolvedValueOnce( {
				status: 'error',
				error: 'Error message',
			} );

			render( <Installer slug="test" source="wporg" /> );
			await act( async () => {
				await component.install();
			} );
			expect( component.error ).toEqual( 'Error message' );
		} );
	} );

	describe( 'Woocommerce.com plugin', () => {
		it( 'opens cart', async () => {
			window.open = jest.fn();
			window.sensei_extensions_data = {
				wccom: {
					'wccom-site': 'http://localhost',
					'wccom-woo-version': '4.0.0',
					'wccom-connect-nonce': '0000',
				},
			};

			render( <Installer slug="123" source="wccom" /> );
			await act( async () => {
				await component.install();
			} );

			expect( window.open ).toHaveBeenCalledWith(
				'https://woocommerce.com/cart?wccom-replace-with=123&wccom-site=http%3A%2F%2Flocalhost&wccom-woo-version=4.0.0&wccom-connect-nonce=0000'
			);
		} );
	} );
} );
