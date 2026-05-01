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

describe( '<ExportSelectItemsPage />', () => {
	beforeEach( () => {
		apiFetch.mockReset();
		apiFetch.mockResolvedValue( [] );
	} );

	it( 'fetches items for each picked type from the WP REST API', async () => {
		render(
			<ExportSelectItemsPage
				types={ [ 'course', 'lesson' ] }
				onSubmit={ () => {} }
				onBack={ () => {} }
			/>
		);

		await waitFor( () => {
			expect( apiFetch ).toHaveBeenCalled();
		} );

		const paths = apiFetch.mock.calls.map( ( [ args ] ) => args.path );
		expect( paths.some( ( p ) => p.startsWith( '/wp/v2/courses' ) ) ).toBe(
			true
		);
		expect( paths.some( ( p ) => p.startsWith( '/wp/v2/lessons' ) ) ).toBe(
			true
		);
		expect( paths.every( ( p ) => p.includes( 'status=any' ) ) ).toBe(
			true
		);
	} );

	it( 'submits empty selections when nothing is picked', async () => {
		const onSubmit = jest.fn();
		const { getByRole } = render(
			<ExportSelectItemsPage
				types={ [ 'course' ] }
				onSubmit={ onSubmit }
				onBack={ () => {} }
			/>
		);

		await waitFor( () => expect( apiFetch ).toHaveBeenCalled() );

		fireEvent.click( getByRole( 'button', { name: 'Start Export' } ) );

		expect( onSubmit ).toHaveBeenCalledWith( {
			types: [ 'course' ],
			selections: { course: [] },
		} );
	} );

	it( 'invokes onBack when the Back button is clicked', async () => {
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
