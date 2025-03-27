/**
 * WordPress dependencies
 */
import { dispatch, select } from '@wordpress/data';
/**
 * External dependencies
 */
import userEvent from '@testing-library/user-event';
import { render } from '@testing-library/react';
/**
 * Internal dependencies
 */
import { BLOCK_META_STORE, withBlockMeta } from './block-metadata';

const STORE = BLOCK_META_STORE;
describe( 'Block metadata', () => {
	beforeEach( () => {
		dispatch( STORE ).clear();
	} );
	describe( 'Block metadata store', () => {
		it( 'Stores data by clientID', () => {
			const clientId = 'test-block-1';

			dispatch( STORE ).setBlockMeta( clientId, { test: true } );
			dispatch( STORE ).setBlockMeta( clientId, { secondary: 'test' } );
			dispatch( STORE ).setBlockMeta( 'different-id', {
				test: false,
			} );
			const meta = select( STORE ).getBlockMeta( clientId );

			expect( meta ).toEqual( { test: true, secondary: 'test' } );
		} );

		it( 'Updates existing metadata', () => {
			const clientId = 'test-block-2';

			dispatch( STORE ).setBlockMeta( clientId, { test: 1 } );
			dispatch( STORE ).setBlockMeta( clientId, { test: 2 } );
			const meta = select( STORE ).getBlockMeta( clientId );

			expect( meta ).toEqual( { test: 2 } );
		} );

		it( 'Selects metadata for multiple blocks', () => {
			dispatch( STORE ).setBlockMeta( 'block-A', { test: 1 } );
			dispatch( STORE ).setBlockMeta( 'block-B', { test: 2 } );
			dispatch( STORE ).setBlockMeta( 'block-C', { test: 3 } );
			const meta = select( STORE ).getMultipleBlockMeta( [
				'block-A',
				'block-B',
			] );

			expect( meta ).toEqual( {
				'block-A': { test: 1 },
				'block-B': { test: 2 },
			} );
		} );

		it( 'Selects single metadata key', () => {
			dispatch( STORE ).setBlockMeta( 'block-A', { test: 15 } );
			const meta = select( STORE ).getBlockMeta( 'block-A', 'test' );

			expect( meta ).toEqual( 15 );
		} );

		it( 'Selects single metadata key for multiple blocks', () => {
			dispatch( STORE ).setBlockMeta( 'block-A', { test: 1 } );
			dispatch( STORE ).setBlockMeta( 'block-B', { test: 2 } );
			dispatch( STORE ).setBlockMeta( 'block-C', { test: 3 } );

			const meta = select( STORE ).getMultipleBlockMeta(
				[ 'block-A', 'block-B' ],
				'test'
			);

			expect( meta ).toEqual( {
				'block-A': 1,
				'block-B': 2,
			} );
		} );
	} );

	describe( 'withBlockMeta', () => {
		it( 'Provides metadata to block', () => {
			dispatch( STORE ).setBlockMeta( 'test-block-3', {
				testString: 'Block metadata',
			} );
			const Block = withBlockMeta( ( { meta } ) => (
				<span>{ meta.testString }</span>
			) );
			const { getByText } = render( <Block clientId="test-block-3" /> );

			expect( getByText( 'Block metadata' ) ).toBeTruthy();
		} );

		it( 'Provides metadata setter to block', async () => {
			const Block = withBlockMeta( ( { setMeta } ) => (
				<button
					onClick={ () => setMeta( { secondTestMeta: 'clicked' } ) }
				>
					Update
				</button>
			) );
			const { getByText } = render( <Block clientId="test-block-4" /> );
			await userEvent.click( getByText( 'Update' ) );

			const meta = select( STORE ).getBlockMeta(
				'test-block-4',
				'secondTestMeta'
			);
			expect( meta ).toEqual( 'clicked' );
		} );
	} );
} );
