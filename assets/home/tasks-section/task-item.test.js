/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import TaskItem from './task-item';

describe( '<TaskItem />', () => {
	it( 'Should render an anchor when item is not completed', () => {
		const { container } = render( <TaskItem href="#" /> );

		const renderedTag = container.querySelector(
			'.sensei-home-tasks__link'
		).tagName;

		expect( renderedTag ).toEqual( 'A' );
	} );

	it( 'Should render a span when item is completed', () => {
		const { container } = render( <TaskItem href="#" completed /> );

		const renderedTag = container.querySelector(
			'.sensei-home-tasks__link'
		).tagName;

		expect( renderedTag ).toEqual( 'SPAN' );
	} );
} );
