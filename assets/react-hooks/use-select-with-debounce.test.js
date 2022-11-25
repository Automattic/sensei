/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useSelectWithDebounce from './use-select-with-debounce';

jest.mock( '@wordpress/data' );

describe( 'useSelectWithDebounce', () => {
	it( 'Should change the deps to call useSelect after timer only', () => {
		jest.useFakeTimers();
		const mapSelect = () => {};
		const timer = 1000;
		const mockFn = jest.fn();
		useSelect.mockImplementation( mockFn );

		const TestComponent = ( { deps } ) => {
			useSelectWithDebounce( mapSelect, deps, timer );
			return <div />;
		};

		const deps1 = [ 1 ];
		const deps2 = [ 2 ];

		const { rerender } = render( <TestComponent deps={ deps1 } /> );
		expect( mockFn ).toBeCalledWith( mapSelect, deps1 );

		rerender( <TestComponent deps={ deps2 } /> );
		expect( mockFn ).toBeCalledWith( mapSelect, deps1 );

		// TODO: Complete test running debounce to make sure it was called after the time.
		// jest.runAllTimers();
		// expect( mockFn ).toBeCalledWith( mapSelect, deps2 );
	} );
} );
