/**
 * External dependencies
 */
import { render, waitFor } from '@testing-library/react';
import nock from 'nock';

/**
 * Internal dependencies
 */
import TasksSection from './index';

describe( '<TasksSection />', () => {
	it( 'Should render tasks section properly', () => {
		const data = {
			is_completed: false,
			items: [
				{ id: '1', title: 'Task 1', done: true },
				{ id: '2', title: 'Task 2', done: false },
			],
		};

		const { queryByText } = render( <TasksSection data={ data } /> );

		expect( queryByText( 'Task 1' ) ).toBeTruthy();
		expect( queryByText( 'Task 2' ) ).toBeTruthy();
		expect(
			queryByText(
				'Your new course is ready to meet its students! Share it with the world.'
			)
		).toBeFalsy();
	} );

	it( 'Should render ready state when tasks are completed', () => {
		const data = {
			is_completed: true,
			items: [],
		};

		const { queryByText } = render( <TasksSection data={ data } /> );

		expect(
			queryByText(
				'Your new course is ready to meet its students! Share it with the world.'
			)
		).toBeTruthy();
	} );

	it( 'Should post to the server that all tasks are completed', async () => {
		const data = {
			is_completed: false,
			items: [
				{ id: '1', title: 'Task 1', done: true },
				{ id: '2', title: 'Task 2', done: true },
			],
		};

		nock( 'http://localhost' )
			.post( '/sensei-internal/v1/home/tasks/complete' )
			.query( {
				_locale: 'user',
			} )
			.reply( 200, {} );

		const { queryByText } = render( <TasksSection data={ data } /> );

		await waitFor( () => {
			expect(
				queryByText(
					'Your new course is ready to meet its students! Share it with the world.'
				)
			).toBeTruthy();
		} );
	} );

	it( 'Should display error when request to complete fails', async () => {
		const data = {
			is_completed: false,
			items: [
				{ id: '1', title: 'Task 1', done: true },
				{ id: '2', title: 'Task 2', done: true },
			],
		};
		const errorMessage = 'Error message';

		nock( 'http://localhost' )
			.post( '/sensei-internal/v1/home/tasks/complete' )
			.query( {
				_locale: 'user',
			} )
			.reply( 400, { message: errorMessage } );

		const { queryByText } = render( <TasksSection data={ data } /> );

		await waitFor( () => {
			expect( queryByText( errorMessage ) ).toBeTruthy();
		} );
	} );
} );
