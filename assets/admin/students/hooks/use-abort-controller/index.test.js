/**
 * Internal dependencies
 */
import useAbortController from '.';

/**
 * External dependencies
 */
import { cleanup, renderHook } from '@testing-library/react';

describe( 'useAbortController', () => {
	it( 'Should return signal to be used on async operations', () => {
		const { result } = renderHook( () => useAbortController() );
		expect( result.current.getSignal() ).toEqual(
			new AbortController().signal
		);
	} );

	it( 'Should abort async operations on component unmount', () => {
		const { result } = renderHook( () => useAbortController() );
		const signal = result.current.getSignal();
		cleanup();
		expect( signal.aborted ).toBe( true );
	} );
} );
