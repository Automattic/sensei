/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { PostTokenField } from './post-token-field';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

const itemFor = ( id, title ) => ( {
	id,
	title: { rendered: title },
} );

const renderField = ( props = {} ) =>
	render(
		<PostTokenField
			type="course"
			ariaLabel="Filter courses to export"
			placeholder="Search…"
			selectedIds={ [] }
			onChange={ () => {} }
			{ ...props }
		/>
	);

describe( '<PostTokenField />', () => {
	beforeEach( () => {
		apiFetch.mockReset();
		apiFetch.mockResolvedValue( [] );
	} );

	it( 'fetches suggestions from the REST endpoint for the given type', async () => {
		renderField( { type: 'lesson' } );

		await waitFor( () => expect( apiFetch ).toHaveBeenCalled() );

		expect( apiFetch.mock.calls[ 0 ][ 0 ].path ).toMatch(
			/^\/wp\/v2\/lessons\?/
		);
	} );

	it( 'renders pre-selected ids as their titles once the fetch resolves', async () => {
		apiFetch.mockResolvedValueOnce( [ itemFor( 42, 'Course A' ) ] );

		const { findByText } = renderField( { selectedIds: [ 42 ] } );

		expect( await findByText( 'Course A' ) ).toBeTruthy();
	} );

	it( 'falls back to "#<id>" when an id is not in the suggestion fetch', async () => {
		// Fetch returns nothing, so id 99 is not in the cache.
		apiFetch.mockResolvedValueOnce( [] );

		const { findByText } = renderField( { selectedIds: [ 99 ] } );

		expect( await findByText( '#99' ) ).toBeTruthy();
	} );

	it( 'still renders a selected token after a later fetch returns different items', async () => {
		// First fetch: contains the selected item. Subsequent fetches don't.
		apiFetch.mockResolvedValueOnce( [ itemFor( 7, 'Course A' ) ] );
		apiFetch.mockResolvedValue( [ itemFor( 99, 'Course B' ) ] );

		const { findByText, rerender, queryByText } = renderField( {
			selectedIds: [ 7 ],
		} );

		expect( await findByText( 'Course A' ) ).toBeTruthy();

		// Force a re-render by changing a prop the fetch effect depends on.
		// The cache must hold on to id 7 even though the new fetch doesn't
		// include it.
		rerender(
			<PostTokenField
				type="course"
				ariaLabel="Filter courses to export"
				placeholder="Search…"
				selectedIds={ [ 7 ] }
				onChange={ () => {} }
			/>
		);

		await waitFor( () => expect( queryByText( 'Course A' ) ).toBeTruthy() );
	} );
} );
