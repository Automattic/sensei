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
import { StudentModal } from './index';

const coursePromise = Promise.resolve( [
	{
		id: 1,
		title: { rendered: 'My Course' },
	},
] );

jest.mock( '@wordpress/api-fetch', () => jest.fn() );
apiFetch.mockImplementation( () => coursePromise );

describe( '<StudentModal />', () => {
	it( 'Should display the "Add to Course" button when adding a course', async () => {
		await act( async () => {
			render( <StudentModal action="add" /> );
		} );

		expect(
			screen.getByRole( 'button', { name: 'Add to Course' } )
		).toBeTruthy();
	} );

	it( 'Should display the "Remove from Course" button when removing a course', async () => {
		await act( async () => {
			render( <StudentModal action="remove" /> );
		} );

		expect(
			screen.getByRole( 'button', { name: 'Remove from Course' } )
		).toBeTruthy();
	} );
} );
