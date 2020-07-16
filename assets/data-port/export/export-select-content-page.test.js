import { render, fireEvent } from '@testing-library/react';
import { ExportSelectContentPage } from './export-select-content-page';

describe( '<SelectExportContentPage />', () => {
	it( 'should allow selecting content types', () => {
		const onSubmit = jest.fn();
		const { getByRole, getByLabelText } = render(
			<ExportSelectContentPage onSubmit={ onSubmit } />
		);

		fireEvent.click( getByLabelText( 'Lessons' ) );
		fireEvent.click( getByRole( 'button', { name: 'Generate CSV' } ) );
		expect( onSubmit ).toHaveBeenCalledWith( {
			course: false,
			lesson: true,
			question: false,
		} );
	} );

	it( 'should disable submit button until one is selected', () => {
		const onSubmit = jest.fn();
		const { getByRole, getByLabelText } = render(
			<ExportSelectContentPage onSubmit={ onSubmit } />
		);

		expect(
			getByRole( 'button', { name: 'Generate CSV' } ).disabled
		).toBeTruthy();
		fireEvent.click( getByLabelText( 'Lessons' ) );
		expect(
			getByRole( 'button', { name: 'Generate CSV' } ).disabled
		).toBeFalsy();
	} );
} );
