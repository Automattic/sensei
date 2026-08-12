/**
 * Internal dependencies
 */
import { hasSomeBlocks } from './blocks-toggling-control';

jest.mock( '@wordpress/data', () =>
	require( '../../../tests/mocks/wordpress-data' )( {
		dispatch: jest.fn( () => new Proxy( {}, { get: () => jest.fn() } ) ),
		select: jest.fn( () => new Proxy( {}, { get: () => jest.fn() } ) ),
	} )
);

describe( 'hasSomeBlocks', () => {
	it( 'Should find a matching nested block', () => {
		const blocks = [
			{
				name: 'core/group',
				innerBlocks: [ { name: 'sensei-lms/course-progress' } ],
			},
		];

		const result = hasSomeBlocks(
			[ 'sensei-lms/course-progress' ],
			blocks
		);

		expect( result ).toBe( true );
	} );

	it( 'Should return false when no matching block exists', () => {
		const blocks = [ { name: 'core/paragraph' } ];

		const result = hasSomeBlocks(
			[ 'sensei-lms/course-progress' ],
			blocks
		);

		expect( result ).toBe( false );
	} );
} );
