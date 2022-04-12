/**
 * External dependencies
 */
import { act, fireEvent, render, screen } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { DOWN } from '@wordpress/keycodes';

/**
 * Internal dependencies
 */
import { StudentActionMenu } from './index';

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

describe( '<StudentActionMenu />', () => {
	it( 'Should display modal when "Add to Course" is selected', async () => {
		apiFetch.mockImplementation( () => Promise.resolve( [] ) );
		render( <StudentActionMenu /> );

		// Open the dropdown menu.
		const button = screen.getByRole( 'button' );

		button.focus();
		fireEvent.keyDown( button, {
			keyCode: DOWN,
			preventDefault: () => {},
		} );

		// Click the "Add to Course" menu item.
		const menuItem = screen.getByText( 'Add to Course' );

		await act( async () => {
			fireEvent.click( menuItem );
		} );

		expect( screen.getByRole( 'dialog' ) ).toBeTruthy();
	} );

	it( 'Should display modal when "Remove from Course" is selected', async () => {
		apiFetch.mockImplementation( () => Promise.resolve( [] ) );
		render( <StudentActionMenu /> );

		// Open the dropdown menu.
		const button = screen.getByRole( 'button' );

		button.focus();
		fireEvent.keyDown( button, {
			keyCode: DOWN,
			preventDefault: () => {},
		} );

		// Click the "Remove from Course" menu item.
		const menuItem = screen.getByText( 'Remove from Course' );

		await act( async () => {
			fireEvent.click( menuItem );
		} );

		expect( screen.getByRole( 'dialog' ) ).toBeTruthy();
	} );

	it( "Should display student's ungraded quizzes when Grading menu item is selected", () => {
		render( <StudentActionMenu studentName="mary" /> );

		// Open the dropdown menu.
		const button = screen.getByRole( 'button' );

		button.focus();
		fireEvent.keyDown( button, {
			keyCode: DOWN,
			preventDefault: () => {},
		} );

		// Click the "Grading" menu item.
		const menuItem = screen.getByText( 'Grading' );
		const windowSpy = jest.spyOn( window, 'open' );

		windowSpy.mockImplementation( () => null );
		fireEvent.click( menuItem );

		expect( windowSpy ).toBeCalledWith(
			'edit.php?post_type=course&page=sensei_grading&view=ungraded&s=mary',
			'_self'
		);

		windowSpy.mockRestore();
	} );
} );
