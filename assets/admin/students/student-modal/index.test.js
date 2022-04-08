/**
 * External dependencies
 */
import { act, render, screen } from '@testing-library/react';
import nock from 'nock';

/**
 * Internal dependencies
 */
import { StudentModal } from './index';

const coursePromise = [
	{
		id: 1,
		title: { rendered: 'My Course' },
	},
];

describe( '<StudentModal />', () => {
	const { getByText, getByRole, findByText, findByRole } = screen;

	beforeEach( () => {
		nock( 'http://localhost' )
			.persist()
			.get( '/wp/v2/courses' )
			.query( { per_page: 100, _locale: 'user' } )
			.reply( 200, coursePromise );
	} );

	it( 'Should display a list of courses', async () => {
		render( <StudentModal action="add" /> );
		expect( await findByText( 'My Course' ) ).toBeTruthy();
	} );

	describe( 'Add action', () => {
		beforeEach( async () => {
			await act( async () => render( <StudentModal action="add" /> ) );
		} );

		it( 'Should display the action description', async () => {
			expect(
				getByText(
					'Select the course(s) you would like to add students to:'
				)
			).toBeTruthy();
		} );

		it( 'Should display the action button', async () => {
			expect(
				getByRole( 'button', { name: 'Add to Course' } )
			).toBeTruthy();
		} );
	} );

	describe( 'Remove action', () => {
		beforeEach( async () => {
			await act( async () => render( <StudentModal action="remove" /> ) );
		} );

		it( 'Should display the action description', async () => {
			expect(
				await findByText(
					'Select the course(s) you would like to remove students from:'
				)
			).toBeTruthy();
		} );

		it( 'Should display the action button', async () => {
			expect(
				await findByRole( 'button', { name: 'Remove from Course' } )
			).toBeTruthy();
		} );
	} );

	describe( 'Reset progress action', () => {
		beforeEach( async () => {
			await act( async () =>
				render( <StudentModal action="reset-progress" /> )
			);
		} );

		it( 'Should display the action description', async () => {
			expect(
				getByText(
					'Select the course(s) you would like to reset or remove progress for:'
				)
			).toBeTruthy();
		} );

		it( 'Should display the action button', async () => {
			expect(
				getByRole( 'button', {
					name: 'Reset or Remove Progress',
				} )
			).toBeTruthy();
		} );
	} );
} );
