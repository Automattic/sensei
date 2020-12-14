import { render, fireEvent } from '@testing-library/react';
import { screen } from '@testing-library/dom';

import ConfirmationModal from './confirmation-modal';

const features = [
	{
		slug: 'first',
		title: 'First',
		excerpt: 'Lorem',
	},
	{
		slug: 'second',
		title: 'Second',
		excerpt: 'Ipsum',
	},
];

describe( '<ConfirmationModal />', () => {
	it( 'Should run the confirmation modal with the items', () => {
		render(
			<ConfirmationModal
				features={ features }
				onInstall={ () => {} }
				onSkip={ () => {} }
			/>
		);

		expect( document.querySelectorAll( 'li' ).length ).toEqual(
			features.length
		);
		expect( screen.queryByText( 'Lorem' ) ).toBeTruthy();
		expect( screen.queryByText( 'Ipsum' ) ).toBeTruthy();
	} );

	it( 'Should call the callbacks', () => {
		const onInstallMock = jest.fn();
		const onSkipMock = jest.fn();

		const { queryByText } = render(
			<ConfirmationModal
				features={ features }
				onInstall={ onInstallMock }
				onSkip={ onSkipMock }
			/>
		);

		fireEvent.click( queryByText( "I'll do it later" ) );
		expect( onSkipMock ).toBeCalled();

		fireEvent.click( queryByText( 'Install now' ) );
		expect( onInstallMock ).toBeCalled();
	} );

	it( 'Should render the confirmation modal with submitting status', () => {
		render(
			<ConfirmationModal
				isSubmitting
				features={ features }
				onInstall={ () => {} }
				onSkip={ () => {} }
			/>
		);

		expect( document.querySelectorAll( 'button:disabled' ) ).toHaveLength(
			2
		);
	} );

	it( 'Should render the confirmation modal with error', () => {
		const { queryByText } = render(
			<ConfirmationModal
				features={ features }
				onInstall={ () => {} }
				onSkip={ () => {} }
				errorNotice="Error"
			/>
		);

		expect( queryByText( 'Error' ) ).toBeTruthy();
	} );
} );
