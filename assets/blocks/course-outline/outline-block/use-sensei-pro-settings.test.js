/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';
/**
 * Internal dependencies
 */
import useSenseiProSettings from './use-sensei-pro-settings';

const mockGetSenseiProExtension = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	select: () => ( {
		getSenseiProExtension: mockGetSenseiProExtension,
	} ),
	createRegistrySelector: jest.fn(),
	createReduxStore: jest.fn(),
	register: jest.fn(),
} ) );

const DEFAULT_EXTENSION_VALUES = {
	title: 'Sensei Pro',
	image: null,
	image_large: null,
	is_featured: false,
	product_slug: 'sensei-pro',
	hosted_location: 'internal',
	type: 'plugin',
	plugin_file: 'sensei-pro/sensei-pro.php',
	version: '',
	is_installed: true,
	is_activated: false,
	installed_version: '1.15.1',
	has_update: false,
	can_update: false,
};

describe( 'useSenseiProSettings', () => {
	describe( '#isActivated', () => {
		it( 'should return false when sensei-pro is not installed', () => {
			const { result } = renderHook( () => useSenseiProSettings() );

			expect( result.current.isActivated ).toBe( false );
		} );

		it( 'should return true when sensei-pro is installed', () => {
			mockGetSenseiProExtension.mockReturnValueOnce( {
				...DEFAULT_EXTENSION_VALUES,
				is_activated: true,
			} );

			const { result } = renderHook( () => useSenseiProSettings() );

			expect( result.current.isActivated ).toBe( true );
		} );
	} );
} );
