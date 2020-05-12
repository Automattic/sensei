import { render, fireEvent } from '@testing-library/react';

import InstallationFeedback from './installation-feedback';
import { LOADING_STATUS, ERROR_STATUS, SUCCESS_STATUS } from './features-list';

describe( '<InstallationFeedback />', () => {
	it( 'Should render with loading status', () => {
		const features = [
			{
				id: 'first',
				title: 'Test',
				description: 'Test',
				status: LOADING_STATUS,
			},
			{
				id: 'second',
				title: 'Test',
				description: 'Test',
				status: ERROR_STATUS,
			},
			{
				id: 'third',
				title: 'Test',
				description: 'Test',
				status: SUCCESS_STATUS,
			},
		];

		const { queryByText } = render(
			<InstallationFeedback
				features={ features }
				onContinue={ () => {} }
			/>
		);

		expect( queryByText( 'Installing…' ) ).toBeTruthy();
	} );

	it( 'Should render all success', () => {
		const features = [
			{
				id: 'first',
				title: 'Test',
				description: 'Test',
				status: SUCCESS_STATUS,
			},
		];

		const onContinueMock = jest.fn();

		const { container, queryByText } = render(
			<InstallationFeedback
				features={ features }
				onContinue={ onContinueMock }
			/>
		);

		expect( container.querySelectorAll( 'button' ).length ).toEqual( 1 );

		fireEvent.click( queryByText( 'Continue' ) );
		expect( onContinueMock ).toBeCalled();
	} );

	it( 'Should render errors without loading', () => {
		const features = [
			{
				id: 'first',
				title: 'Test',
				description: 'Test',
				status: ERROR_STATUS,
			},
			{
				id: 'second',
				title: 'Test',
				description: 'Test',
				status: SUCCESS_STATUS,
			},
		];

		const onContinueMock = jest.fn();

		const { queryByText } = render(
			<InstallationFeedback
				features={ features }
				onContinue={ onContinueMock }
			/>
		);

		expect( queryByText( 'Retry' ) ).toBeTruthy();

		fireEvent.click( queryByText( 'Continue' ) );
		expect( onContinueMock ).toBeCalled();
	} );
} );
