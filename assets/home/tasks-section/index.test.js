/**
 * External dependencies
 */
import { render } from '@testing-library/react';

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
} );
