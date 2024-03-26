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
		const { container } = render( <TaskItem url="#" /> );

		const renderedTag = container.querySelector(
			'.sensei-home-tasks__link'
		).tagName;

		expect( renderedTag ).toEqual( 'A' );
	} );

	it( 'Should render a span when item is completed', () => {
		const { container } = render( <TaskItem url="#" done /> );

		const renderedTag = container.querySelector(
			'.sensei-home-tasks__link'
		).tagName;

		expect( renderedTag ).toEqual( 'SPAN' );
	} );

	it( 'Should render an external icon when externalIcon prop is true', () => {
		const { container } = render( <TaskItem url="#" externalIcon /> );

		const externalIcon = container.querySelector(
			'.sensei-home-tasks__external-icon'
		);

		expect( externalIcon ).not.toBeNull();
	} );
} );
