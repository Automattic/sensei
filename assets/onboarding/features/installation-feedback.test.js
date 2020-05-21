import { render, fireEvent } from '@testing-library/react';

import InstallationFeedback from './installation-feedback';
import {
	INSTALLING_STATUS,
	ERROR_STATUS,
	INSTALLED_STATUS,
} from './feature-status';

describe( '<InstallationFeedback />', () => {
	it( 'Should render with loading status', () => {
		const features = [
			{
				title: 'Test',
				excerpt: 'Test',
				status: INSTALLING_STATUS,
			},
			{
				title: 'Test',
				excerpt: 'Test',
				status: ERROR_STATUS,
			},
			{
				title: 'Test',
				excerpt: 'Test',
				status: INSTALLED_STATUS,
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
				title: 'Test',
				excerpt: 'Test',
				status: INSTALLED_STATUS,
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
				title: 'Test',
				excerpt: 'Test',
				status: ERROR_STATUS,
			},
			{
				title: 'Test',
				excerpt: 'Test',
				status: INSTALLED_STATUS,
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
