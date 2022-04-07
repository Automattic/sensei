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
	describe( 'Add action', () => {
		beforeEach( async () => {
			await act( async () => {
				return render( <StudentModal action="add" /> );
			} );
		} );

		it( 'Should display the action description', async () => {
			expect(
				screen.getByText(
					'Select the course(s) you would like to add students to:'
				)
			).toBeTruthy();
		} );

		it( 'Should display the action button', async () => {
			expect(
				screen.getByRole( 'button', { name: 'Add to Course' } )
			).toBeTruthy();
		} );
	} );

	describe( 'Remove action', () => {
		beforeEach( async () => {
			await act( async () => {
				return render( <StudentModal action="remove" /> );
			} );
		} );

		it( 'Should display the action description', async () => {
			expect(
				screen.getByText(
					'Select the course(s) you would like to remove students from:'
				)
			).toBeTruthy();
		} );

		it( 'Should display the action button', async () => {
			expect(
				screen.getByRole( 'button', { name: 'Remove from Course' } )
			).toBeTruthy();
		} );
	} );

	describe( 'reset progress action', () => {
		beforeEach( async () => {
			await act( async () => {
				return render( <StudentModal action="reset-progress" /> );
			} );
		} );

		it( 'Should display the action description', async () => {
			expect(
				screen.getByText(
					'Select the course(s) you would like to reset the students progress:'
				)
			).toBeTruthy();
		} );

		it( 'Should display the action button', async () => {
			expect(
				screen.getByRole( 'button', {
					name: 'Reset or Remove the student(s) progress',
				} )
			).toBeTruthy();
		} );
	} );
} );
