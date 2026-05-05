/**
 * External dependencies
 */
import { fireEvent, render, waitFor } from '@testing-library/react';

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
	beforeAll( () => {
		// FormTokenField calls scrollIntoView on the auto-selected suggestion;
		// jsdom doesn't implement it.
		// eslint-disable-next-line no-undef
		Element.prototype.scrollIntoView = jest.fn();
	} );

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

	it( 'still renders a selected token after a search returns different items', async () => {
		// First fetch: contains the selected item. Subsequent fetches don't.
		apiFetch.mockResolvedValueOnce( [ itemFor( 7, 'Course A' ) ] );
		apiFetch.mockResolvedValue( [ itemFor( 99, 'Course B' ) ] );

		const { findByText, getByRole, queryByText } = renderField( {
			selectedIds: [ 7 ],
		} );

		expect( await findByText( 'Course A' ) ).toBeTruthy();

		// Type into the field. The debounce useEffect pushes the value into
		// debouncedInput 300ms later, which re-fires the fetch.
		fireEvent.change( getByRole( 'combobox' ), {
			target: { value: 'B' },
		} );

		await waitFor( () => expect( apiFetch ).toHaveBeenCalledTimes( 2 ) );

		// The cache holds id 7 even though the new fetch didn't return it,
		// so the token's title is still on screen.
		expect( queryByText( 'Course A' ) ).toBeTruthy();
	} );
} );
