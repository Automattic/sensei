/**
 * External dependencies
 */
import { act, renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { useMergeReducer } from './use-merge-reducer';

describe( 'useMergeReducer', () => {
	it( 'partially updates the state', () => {
		const { result } = renderHook( () =>
			useMergeReducer( {
				a: { x: 'original' },
				b: { y: 'original' },
			} )
		);

		act( () => {
			const [ , updateState ] = result.current;
			updateState( { a: { z: 'updated' } } );
		} );

		const [ state ] = result.current;
		expect( state ).toEqual( {
			a: { z: 'updated' },
			b: { y: 'original' },
		} );
	} );
} );
