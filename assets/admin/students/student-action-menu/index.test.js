/**
 * External dependencies
 */
import { act, fireEvent, render, screen } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import { DOWN } from '@wordpress/keycodes';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { StudentActionMenu } from './index';

jest.mock( '@wordpress/data' );

const studentName = 'johndoe';
const studentDisplayName = 'John Doe';

window.ResizeObserver =
	window.ResizeObserver ||
	jest.fn().mockImplementation( () => ( {
		disconnect: jest.fn(),
		observe: jest.fn(),
		unobserve: jest.fn(),
	} ) );

describe( '<StudentActionMenu />', () => {
	it( 'Should display modal when "Add to Course" is selected', async () => {
		useSelect.mockReturnValue( { courses: [], isFetching: false } );
		render(
			<StudentActionMenu studentDisplayName={ studentDisplayName } />
		);

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
		useSelect.mockReturnValue( { courses: [], isFetching: false } );
		render(
			<StudentActionMenu studentDisplayName={ studentDisplayName } />
		);

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

	it( 'Should display modal when "Reset progress" is selected', async () => {
		useSelect.mockReturnValue( { courses: [], isFetching: false } );
		render(
			<StudentActionMenu studentDisplayName={ studentDisplayName } />
		);

		// Open the dropdown menu.
		const button = screen.getByRole( 'button' );

		button.focus();
		fireEvent.keyDown( button, {
			keyCode: DOWN,
			preventDefault: () => {},
		} );

		// Click the "Reset Progress" menu item.
		const menuItem = screen.getByText( 'Reset Progress' );

		await act( async () => {
			fireEvent.click( menuItem );
		} );

		expect( screen.getByRole( 'dialog' ) ).toBeTruthy();
	} );

	it( "Should display student's ungraded quizzes when Grading menu item is selected", () => {
		render(
			<StudentActionMenu
				studentName={ studentName }
				studentDisplayName={ studentDisplayName }
			/>
		);

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
			`admin.php?page=sensei_grading&view=ungraded&s=${ studentName }`,
			'_self'
		);

		windowSpy.mockRestore();
	} );
} );
