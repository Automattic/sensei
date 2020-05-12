import { render, fireEvent } from '@testing-library/react';
import { screen } from '@testing-library/dom';

import ConfirmationModal from './confirmation-modal';

const features = [
	{
		title: 'First',
		description: 'Dolor',
	},
	{
		title: 'Second',
		description: 'Lorem',
		confirmationExtraDescription: 'Ipsum',
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
		expect( screen.queryByText( 'Dolor' ) ).toBeTruthy();
		expect( screen.queryByText( 'Lorem Ipsum' ) ).toBeTruthy();
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
} );
