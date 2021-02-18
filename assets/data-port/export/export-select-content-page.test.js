/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { ExportSelectContentPage } from './export-select-content-page';

describe( '<SelectExportContentPage />', () => {
	it( 'allows selecting content types', () => {
		const onSubmit = jest.fn();
		const { getByRole, getByLabelText } = render(
			<ExportSelectContentPage onSubmit={ onSubmit } />
		);

		fireEvent.click( getByLabelText( 'Lessons' ) );
		fireEvent.click( getByRole( 'button', { name: 'Continue' } ) );
		expect( onSubmit ).toHaveBeenCalledWith( [ 'lesson' ] );
	} );

	it( 'disables submit button until one is selected', () => {
		const onSubmit = jest.fn();
		const { getByRole, getByLabelText } = render(
			<ExportSelectContentPage onSubmit={ onSubmit } />
		);

		expect(
			getByRole( 'button', { name: 'Continue' } ).disabled
		).toBeTruthy();
		fireEvent.click( getByLabelText( 'Lessons' ) );
		expect(
			getByRole( 'button', { name: 'Continue' } ).disabled
		).toBeFalsy();
	} );
} );
