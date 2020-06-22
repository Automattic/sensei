import { render, act } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';

import useProgressPolling from './use-progress-polling';

jest.mock( '@wordpress/data', () => ( {
	useSelect: () => {},
	useDispatch: jest.fn(),
} ) );

describe( 'useProgressPolling', () => {
	const invalidateResolutionMock = jest.fn();

	useDispatch.mockImplementation( () => ( {
		invalidateResolution: invalidateResolutionMock,
	} ) );

	afterEach( () => {
		invalidateResolutionMock.mockReset();
	} );

	it( 'Should invalidate resolution after the timer if polling is active', () => {
		const TestComponent = () => {
			useProgressPolling( true, 'test-job' );

			return <div />;
		};

		jest.useFakeTimers();
		render( <TestComponent /> );
		act( () => {
			jest.runOnlyPendingTimers();
		} );

		expect( invalidateResolutionMock ).toBeCalled();
	} );

	it( 'Should not invalidate resolution after the timer if polling is not active', () => {
		const TestComponent = () => {
			useProgressPolling( false, 'test-job' );

			return <div />;
		};

		jest.useFakeTimers();
		render( <TestComponent /> );
		jest.runOnlyPendingTimers();

		expect( invalidateResolutionMock ).not.toBeCalled();
	} );
} );
