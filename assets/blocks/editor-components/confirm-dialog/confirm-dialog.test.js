/**
 * WordPress dependencies
 */
import { ENTER } from '@wordpress/keycodes';

/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ConfirmDialog from './confirm-dialog';

describe( '<ConfirmDialog />', () => {
	it( 'Should not render if the dialog is not open', () => {
		const { queryByText } = render(
			<ConfirmDialog isOpen={ false } title="Hey Title">
				Hey Content
			</ConfirmDialog>
		);

		expect( queryByText( 'Hey Title' ) ).toBeFalsy();
		expect( queryByText( 'Hey Content' ) ).toBeFalsy();
	} );

	it( 'Should render modal if the dialog is open', () => {
		const { queryByText } = render(
			<ConfirmDialog isOpen={ true } title="Hey Title">
				Hey Content
			</ConfirmDialog>
		);

		expect( queryByText( 'Hey Title' ) ).toBeTruthy();
		expect( queryByText( 'Hey Content' ) ).toBeTruthy();
	} );

	it( 'Should cancel the modal if click on Cancel button', () => {
		const onCancel = jest.fn();
		const { queryByText } = render(
			<ConfirmDialog
				isOpen={ true }
				title="Hey Title"
				onCancel={ onCancel }
			>
				Hey Content
			</ConfirmDialog>
		);

		expect( onCancel ).not.toHaveBeenCalled();
		fireEvent.click( queryByText( 'Cancel' ) );
		expect( onCancel ).toHaveBeenCalled();
	} );

	it( 'Should confirm the modal if click on OK button', () => {
		const onConfirm = jest.fn();
		const { queryByText } = render(
			<ConfirmDialog
				isOpen={ true }
				title="Hey Title"
				onConfirm={ onConfirm }
			>
				Hey Content
			</ConfirmDialog>
		);

		expect( onConfirm ).not.toHaveBeenCalled();
		fireEvent.click( queryByText( 'OK' ) );
		expect( onConfirm ).toHaveBeenCalled();
	} );

	it( 'Should confirm the modal if the user press ENTER', () => {
		const onConfirm = jest.fn();
		render(
			<ConfirmDialog
				isOpen={ true }
				title="Hey Title"
				onConfirm={ onConfirm }
			>
				Hey Content
			</ConfirmDialog>
		);

		expect( onConfirm ).not.toHaveBeenCalled();
		fireEvent.keyDown( document.body, {
			key: 'Enter',
			code: 'Enter',
			keyCode: ENTER,
		} );
		expect( onConfirm ).toHaveBeenCalled();
	} );
} );
