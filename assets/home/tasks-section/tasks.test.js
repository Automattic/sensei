/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Tasks from './tasks';

describe( '<Tasks />', () => {
	it( 'Should render all the tasks', () => {
		const items = [
			{ id: '1', title: 'Task 1', done: true },
			{ id: '2', title: 'Task 2', done: false },
		];

		const { getAllByRole } = render( <Tasks items={ items } /> );

		expect( getAllByRole( 'listitem' ).length ).toEqual( 2 );
	} );
} );
