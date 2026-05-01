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
import { ExportSelectItemsPage } from './export-select-items-page';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const buildResponse = ( items, totalPages = 1 ) => ( {
	headers: { get: () => String( totalPages ) },
	json: async () => items,
} );

describe( '<ExportSelectItemsPage />', () => {
	beforeEach( () => {
		apiFetch.mockReset();
	} );

	it( 'submits empty selections when nothing is picked', async () => {
		apiFetch.mockResolvedValue(
			buildResponse( [
				{ id: 1, title: { rendered: 'Course A' } },
				{ id: 2, title: { rendered: 'Course B' } },
			] )
		);

		const onSubmit = jest.fn();
		const { getByRole } = render(
			<ExportSelectItemsPage
				types={ [ 'course' ] }
				onSubmit={ onSubmit }
				onBack={ () => {} }
			/>
		);

		await waitFor( () => getByRole( 'checkbox', { name: 'Course A' } ) );

		fireEvent.click( getByRole( 'button', { name: 'Start Export' } ) );

		expect( onSubmit ).toHaveBeenCalledWith( {
			types: [ 'course' ],
			selections: { course: [] },
		} );
	} );

	it( 'collects picked IDs per type and forwards them on submit', async () => {
		apiFetch.mockResolvedValue(
			buildResponse( [
				{ id: 12, title: { rendered: 'Course A' } },
				{ id: 34, title: { rendered: 'Course B' } },
			] )
		);

		const onSubmit = jest.fn();
		const { getByRole } = render(
			<ExportSelectItemsPage
				types={ [ 'course' ] }
				onSubmit={ onSubmit }
				onBack={ () => {} }
			/>
		);

		await waitFor( () => getByRole( 'checkbox', { name: 'Course A' } ) );

		fireEvent.click( getByRole( 'checkbox', { name: 'Course A' } ) );
		fireEvent.click( getByRole( 'checkbox', { name: 'Course B' } ) );
		fireEvent.click( getByRole( 'button', { name: 'Start Export' } ) );

		expect( onSubmit ).toHaveBeenCalledWith( {
			types: [ 'course' ],
			selections: { course: [ 12, 34 ] },
		} );
	} );

	it( 'invokes onBack when the Back button is clicked', async () => {
		apiFetch.mockResolvedValue( buildResponse( [] ) );

		const onBack = jest.fn();
		const { getByRole } = render(
			<ExportSelectItemsPage
				types={ [ 'course' ] }
				onSubmit={ () => {} }
				onBack={ onBack }
			/>
		);

		await waitFor( () => expect( apiFetch ).toHaveBeenCalled() );

		fireEvent.click( getByRole( 'button', { name: 'Back' } ) );

		expect( onBack ).toHaveBeenCalled();
	} );
} );
