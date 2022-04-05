/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { CourseList } from './course-list';

const coursePromise = Promise.resolve( [
	{
		id: 1,
		title: { rendered: 'My Course' },
	},
	{
		id: 2,
		title: { rendered: 'Another Course' },
	},
] );

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
apiFetch.mockImplementation( () => coursePromise );

describe( '<CourseList />', () => {
	it( 'Should display courses in the list', async () => {
		await act( async () => {
			render( <CourseList /> );
		} );

		expect( screen.getAllByRole( 'listitem' ).length ).toBe( 2 );
	} );
} );
