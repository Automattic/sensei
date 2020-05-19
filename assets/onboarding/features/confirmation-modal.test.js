import { render, fireEvent } from '@testing-library/react';
import { screen } from '@testing-library/dom';

import ConfirmationModal from './confirmation-modal';

const features = [
	{
		id: 'first',
		title: 'First',
		description: 'Dolor',
	},
	{
		id: 'sensei-wc-paid-courses',
		title: 'Second',
		description: 'Lorem',
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
		expect(
			screen.queryByText(
				'Lorem (The WooCommerce plugin may also be installed and activated for free.)'
			)
		).toBeTruthy();
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
