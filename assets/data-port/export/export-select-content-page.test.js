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
import { ExportSelectContentPage } from './export-select-content-page';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

describe( '<ExportSelectContentPage />', () => {
	beforeEach( () => {
		apiFetch.mockReset();
		apiFetch.mockResolvedValue( [] );
	} );

	it( 'submits all three types with empty selections by default', async () => {
		const onSubmit = jest.fn();
		const { getByRole } = render(
			<ExportSelectContentPage onSubmit={ onSubmit } />
		);

		await waitFor( () => expect( apiFetch ).toHaveBeenCalled() );

		fireEvent.click( getByRole( 'button', { name: 'Start Export' } ) );

		expect( onSubmit ).toHaveBeenCalledWith( {
			course: [],
			lesson: [],
			question: [],
		} );
	} );

	it( 'omits a type when its toggle is turned off', async () => {
		const onSubmit = jest.fn();
		const { getByRole, getAllByRole } = render(
			<ExportSelectContentPage onSubmit={ onSubmit } />
		);

		await waitFor( () => expect( apiFetch ).toHaveBeenCalled() );

		// Toggles render in row order: course, lesson, question.
		const toggles = getAllByRole( 'checkbox' );
		fireEvent.click( toggles[ 1 ] ); // turn off Lessons.

		fireEvent.click( getByRole( 'button', { name: 'Start Export' } ) );

		expect( onSubmit ).toHaveBeenCalledWith( {
			course: [],
			question: [],
		} );
	} );

	it( 'disables Start Export when every toggle is off', async () => {
		const { getByRole, getAllByRole } = render(
			<ExportSelectContentPage onSubmit={ () => {} } />
		);

		await waitFor( () => expect( apiFetch ).toHaveBeenCalled() );

		const toggles = getAllByRole( 'checkbox' );
		toggles.forEach( ( toggle ) => fireEvent.click( toggle ) );

		expect( getByRole( 'button', { name: 'Start Export' } ).disabled ).toBe(
			true
		);
	} );

	it( 'renders a summary describing the default export', async () => {
		const { queryByText, queryAllByText } = render(
			<ExportSelectContentPage onSubmit={ () => {} } />
		);

		await waitFor( () => expect( apiFetch ).toHaveBeenCalled() );

		expect(
			queryByText( 'Your export will include:' )
		).toBeInTheDocument();
		// Each type label appears twice (checkbox + summary fallback)
		// when total counts haven't loaded yet.
		expect( queryAllByText( 'Courses' ) ).toHaveLength( 2 );
		expect( queryAllByText( 'Lessons' ) ).toHaveLength( 2 );
		expect( queryAllByText( 'Questions' ) ).toHaveLength( 2 );
	} );

	it( 'shows a row as skipped in the summary when its toggle is off', async () => {
		const { queryByText, getAllByRole } = render(
			<ExportSelectContentPage onSubmit={ () => {} } />
		);

		await waitFor( () => expect( apiFetch ).toHaveBeenCalled() );

		const toggles = getAllByRole( 'checkbox' );
		fireEvent.click( toggles[ 1 ] ); // turn off Lessons.

		expect( queryByText( 'Lessons — skipped' ) ).toBeInTheDocument();
	} );

	it( 'disables Start Export while a job is being created', async () => {
		const { getByRole } = render(
			<ExportSelectContentPage
				onSubmit={ () => {} }
				job={ { status: 'creating' } }
			/>
		);

		await waitFor( () => expect( apiFetch ).toHaveBeenCalled() );

		expect( getByRole( 'button', { name: 'Start Export' } ).disabled ).toBe(
			true
		);
	} );
} );
