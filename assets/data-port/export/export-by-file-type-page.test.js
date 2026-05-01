/**
 * External dependencies
 */
import { render, fireEvent, waitFor } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { ExportByFileTypePage } from './export-by-file-type-page';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

describe( '<ExportByFileTypePage />', () => {
	beforeEach( () => {
		apiFetch.mockReset();
		apiFetch.mockResolvedValue( [] );
	} );

	it( 'submits all three types with empty selections by default', async () => {
		const onSubmit = jest.fn();
		const { getByRole } = render(
			<ExportByFileTypePage onSubmit={ onSubmit } onBack={ () => {} } />
		);

		await waitFor( () => expect( apiFetch ).toHaveBeenCalled() );

		fireEvent.click( getByRole( 'button', { name: 'Start Export' } ) );

		expect( onSubmit ).toHaveBeenCalledWith( {
			types: [ 'course', 'lesson', 'question' ],
			selections: {},
			mode: 'by_file_type',
		} );
	} );

	it( 'omits unchecked rows from the submitted types', async () => {
		const onSubmit = jest.fn();
		const { getByRole, getByLabelText } = render(
			<ExportByFileTypePage onSubmit={ onSubmit } onBack={ () => {} } />
		);

		await waitFor( () => expect( apiFetch ).toHaveBeenCalled() );

		fireEvent.click( getByLabelText( 'lessons.csv' ) );
		fireEvent.click( getByLabelText( 'questions.csv' ) );

		fireEvent.click( getByRole( 'button', { name: 'Start Export' } ) );

		expect( onSubmit ).toHaveBeenCalledWith( {
			types: [ 'course' ],
			selections: {},
			mode: 'by_file_type',
		} );
	} );

	it( 'disables Start Export when every row is unchecked', async () => {
		const { getByRole, getByLabelText } = render(
			<ExportByFileTypePage onSubmit={ () => {} } onBack={ () => {} } />
		);

		await waitFor( () => expect( apiFetch ).toHaveBeenCalled() );

		fireEvent.click( getByLabelText( 'courses.csv' ) );
		fireEvent.click( getByLabelText( 'lessons.csv' ) );
		fireEvent.click( getByLabelText( 'questions.csv' ) );

		expect( getByRole( 'button', { name: 'Start Export' } ).disabled ).toBe(
			true
		);
	} );
} );
