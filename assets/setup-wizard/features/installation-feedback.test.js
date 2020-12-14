import { render, fireEvent, act, screen } from '@testing-library/react';
import { useState, useEffect } from '@wordpress/element';

import InstallationFeedback from './installation-feedback';
import useFeaturesPolling from './use-features-polling';
import {
	INSTALLING_STATUS,
	ERROR_STATUS,
	INSTALLED_STATUS,
	EXTERNAL_STATUS,
} from './feature-status';

// Mock features data.
jest.mock( './use-features-polling', () => jest.fn() );

const featuresOptions = [
	{
		slug: 'test-installing',
		title: 'Test installing',
		excerpt: 'Test installing',
		status: INSTALLING_STATUS,
	},
	{
		slug: 'test-error',
		title: 'Test error',
		excerpt: 'Test error',
		error: 'Error message',
		status: ERROR_STATUS,
	},
	{
		slug: 'test-error-2',
		title: 'Test error 2',
		excerpt: 'Test error 2',
		error: 'Error message',
		status: ERROR_STATUS,
	},
	{
		slug: 'test-installed',
		title: 'Test installed',
		excerpt: 'Test installed',
		status: INSTALLED_STATUS,
	},
	{
		slug: 'test-external',
		title: 'Test External',
		excerpt: 'Test External',
		status: EXTERNAL_STATUS,
	},
	{
		slug: 'test-empty',
		title: 'Test empty status',
		excerpt: 'Test empty status',
	},
];

describe( '<InstallationFeedback />', () => {
	it( 'Should render with loading status', () => {
		const features = {
			selected: [ 'test-installing', 'test-error', 'test-installed' ],
			options: featuresOptions,
		};

		useFeaturesPolling.mockImplementation( () => features );

		const { queryByText } = render(
			<InstallationFeedback
				onContinue={ () => {} }
				onRetry={ () => {} }
			/>
		);

		expect( queryByText( 'Installing…' ) ).toBeTruthy();
	} );

	it( 'Should render all success', () => {
		const features = {
			selected: [ 'test-installed' ],
			options: featuresOptions,
		};

		useFeaturesPolling.mockImplementation( () => features );

		const onContinueMock = jest.fn();

		const { container, queryByText } = render(
			<InstallationFeedback
				onContinue={ onContinueMock }
				onRetry={ () => {} }
			/>
		);

		expect( container.querySelectorAll( 'button' ).length ).toEqual( 1 );

		fireEvent.click( queryByText( 'Continue' ) );
		expect( onContinueMock ).toBeCalled();
	} );

	it( 'Should render errors without loading', () => {
		const features = {
			selected: [ 'test-error', 'test-installed' ],
			options: featuresOptions,
		};

		useFeaturesPolling.mockImplementation( () => features );

		const onContinueMock = jest.fn();

		const { queryByText } = render(
			<InstallationFeedback
				onContinue={ onContinueMock }
				onRetry={ () => {} }
			/>
		);

		expect( queryByText( 'Retry' ) ).toBeTruthy();

		fireEvent.click( queryByText( 'Continue' ) );
		expect( onContinueMock ).toBeCalled();
	} );

	it( 'Should update external plugin status until installed', async () => {
		const onContinueMock = jest.fn();

		jest.useFakeTimers();

		useFeaturesPolling.mockImplementation( ( active ) => {
			const [ pollingCount, setPollingCount ] = useState( 0 );
			useEffect( () => {
				if ( ! active ) {
					return;
				}
				const timer = setTimeout(
					() => setPollingCount( ( n ) => n + 1 ),
					2000
				);
				return () => {
					clearTimeout( timer );
				};
			}, [ active, pollingCount ] );

			return 0 === pollingCount
				? {
						selected: [ 'test-external' ],
						options: [
							{
								slug: 'test-external',
								title: 'Test External',
								excerpt: 'Test External',
								status: EXTERNAL_STATUS,
							},
						],
				  }
				: {
						selected: [ 'test-external' ],
						options: [
							{
								slug: 'test-external',
								title: 'Test External',
								excerpt: 'Test External',
								status: INSTALLED_STATUS,
							},
						],
				  };
		} );

		render(
			<InstallationFeedback
				onContinue={ onContinueMock }
				onRetry={ () => {} }
			/>
		);

		expect( screen.queryByText( 'Continue' ) ).toBeTruthy();

		expect( screen.queryByText( 'Plugin installed' ) ).toBeFalsy();

		act( jest.runAllTimers );

		expect( screen.queryByText( 'Continue' ) ).toBeTruthy();

		expect( screen.queryByText( 'Plugin installed' ) ).toBeTruthy();

		fireEvent.click( screen.queryByText( 'Continue' ) );
		expect( onContinueMock ).toBeCalled();
	} );

	it( 'Should retry installations with error', () => {
		const features = {
			selected: [ 'test-error', 'test-error-2', 'test-installed' ],
			options: featuresOptions,
		};

		useFeaturesPolling.mockImplementation( () => features );

		const onRetryMock = jest.fn();

		const { queryByText, queryAllByText } = render(
			<InstallationFeedback
				onContinue={ () => {} }
				onRetry={ onRetryMock }
			/>
		);

		fireEvent.click( queryByText( 'Retry' ) );
		expect( onRetryMock ).toBeCalledWith( [
			'test-error',
			'test-error-2',
		] );

		onRetryMock.mockReset();

		fireEvent.click( queryAllByText( 'Retry?' )[ 0 ] );
		expect( onRetryMock ).toBeCalledWith( [ 'test-error' ] );
	} );
} );
