import { render, act } from '@testing-library/react';
import { useDispatch } from '@wordpress/data';

import useProgressPolling from './use-progress-polling';

jest.mock( '@wordpress/data', () => ( {
	useSelect: () => {},
	useDispatch: jest.fn(),
} ) );

describe( 'useProgressPolling', () => {
	const updateJobStateMock = jest.fn();

	useDispatch.mockImplementation( () => ( {
		updateJobState: updateJobStateMock,
	} ) );

	afterEach( () => {
		updateJobStateMock.mockReset();
	} );

	it( 'Should dispatch updateJobState after the timer if polling is active', () => {
		const TestComponent = () => {
			useProgressPolling( true, 'test-job' );

			return <div />;
		};

		jest.useFakeTimers();
		render( <TestComponent /> );
		act( () => {
			jest.runOnlyPendingTimers();
		} );

		expect( updateJobStateMock ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'Should not update after the timer if polling is not active', () => {
		const TestComponent = () => {
			useProgressPolling( false, 'test-job' );

			return <div />;
		};

		jest.useFakeTimers();
		render( <TestComponent /> );
		jest.runOnlyPendingTimers();

		expect( updateJobStateMock ).not.toBeCalled();
	} );
} );
