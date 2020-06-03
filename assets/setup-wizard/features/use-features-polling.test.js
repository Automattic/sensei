import { render, act } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';

import useFeaturesPolling from './use-features-polling';

jest.mock( '@wordpress/data', () => ( {
	useSelect: () => {},
	useDispatch: jest.fn(),
} ) );

describe( 'useFeaturesPolling', () => {
	const invalidateResolutionMock = jest.fn();

	useDispatch.mockImplementation( () => ( {
		invalidateResolution: invalidateResolutionMock,
	} ) );

	afterEach( () => {
		invalidateResolutionMock.mockReset();
	} );

	it( 'Should invalidate resolution after the timer if polling is active', () => {
		const TestComponent = () => {
			useFeaturesPolling( true );

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
			useFeaturesPolling( false );

			return <div />;
		};

		jest.useFakeTimers();
		render( <TestComponent /> );
		jest.runOnlyPendingTimers();

		expect( invalidateResolutionMock ).not.toBeCalled();
	} );
} );
