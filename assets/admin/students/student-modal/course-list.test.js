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

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

describe( '<CourseList />', () => {
	it( 'Should display courses in the list', async () => {
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
		apiFetch.mockImplementation( () => coursePromise );

		await act( async () => {
			render( <CourseList /> );
		} );

		expect( screen.getByLabelText( 'My Course' ) ).toBeTruthy();
		expect( screen.getByLabelText( 'Another Course' ) ).toBeTruthy();
	} );

	it( 'Should show a message when there are no courses', async () => {
		apiFetch.mockImplementation( () => Promise.resolve( [] ) );

		await act( async () => {
			render( <CourseList /> );
		} );

		expect( screen.getByText( 'No courses found.' ) ).toBeTruthy();
	} );
} );
