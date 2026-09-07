/**
 * External dependencies
 */
import { fireEvent, render, waitFor } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
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

// Mirrors the parent's cache-merging behaviour so the tests exercise the
// real interaction between the field and its cache owner.
const useItemsCache = () => {
	const [ cachedItems, setCachedItems ] = useState( () => new Map() );
	const onItemsFetched = ( items ) => {
		setCachedItems( ( current ) => {
			const next = new Map( current );
			items.forEach( ( item ) => next.set( item.id, item ) );
			return next;
		} );
	};
	return [ cachedItems, onItemsFetched ];
};

const Harness = ( props ) => {
	const [ cachedItems, onItemsFetched ] = useItemsCache();
	return (
		<PostTokenField
			type="course"
			ariaLabel="Filter courses to export"
			placeholder="Search…"
			selectedIds={ [] }
			onChange={ () => {} }
			cachedItems={ cachedItems }
			onItemsFetched={ onItemsFetched }
			{ ...props }
		/>
	);
};

const renderField = ( props = {} ) => render( <Harness { ...props } /> );

describe( '<PostTokenField />', () => {
	beforeAll( () => {
		// FormTokenField calls scrollIntoView on the auto-selected suggestion;
		// jsdom doesn't implement it.

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

	it( 'does not request relevance ordering when there is no search term', async () => {
		renderField( { type: 'lesson' } );

		await waitFor( () => expect( apiFetch ).toHaveBeenCalled() );

		// WP core rejects `orderby=relevance` without a `search` param, so the
		// initial (empty-search) fetch must fall back to the default ordering.
		expect( apiFetch.mock.calls[ 0 ][ 0 ].path ).not.toContain(
			'orderby=relevance'
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

	it( 'keeps a selected token labelled after the field unmounts and remounts', async () => {
		// First and second mount both fetch suggestions. The second mount's
		// fetch deliberately does not include the selected id, so the only
		// way the title can survive is if the cache outlived the unmount.
		apiFetch.mockResolvedValueOnce( [ itemFor( 7, 'Course A' ) ] );
		apiFetch.mockResolvedValue( [ itemFor( 99, 'Course B' ) ] );

		// Drive the field through a parent that can hide and re-show it
		// (the same shape as ExportSelectContentPage's checkbox-gated row).
		const Toggleable = () => {
			const [ cachedItems, onItemsFetched ] = useItemsCache();
			const [ visible, setVisible ] = useState( true );
			return (
				<>
					<button onClick={ () => setVisible( ( v ) => ! v ) }>
						toggle
					</button>
					{ visible && (
						<PostTokenField
							type="course"
							ariaLabel="Filter courses to export"
							placeholder="Search…"
							selectedIds={ [ 7 ] }
							onChange={ () => {} }
							cachedItems={ cachedItems }
							onItemsFetched={ onItemsFetched }
						/>
					) }
				</>
			);
		};

		const { findByText, getByText, queryByText } = render( <Toggleable /> );

		expect( await findByText( 'Course A' ) ).toBeTruthy();

		fireEvent.click( getByText( 'toggle' ) );
		expect( queryByText( 'Course A' ) ).toBeNull();

		fireEvent.click( getByText( 'toggle' ) );

		// The remount fetch returned only Course B, but the parent-owned cache
		// still has id 7's title — so the token reads "Course A", not "#7".
		expect( await findByText( 'Course A' ) ).toBeTruthy();
		expect( queryByText( '#7' ) ).toBeNull();
	} );
} );
