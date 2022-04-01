/**
 * External dependencies
 */
import { fireEvent, render } from '@testing-library/react';

/**
 * WordPress dependencies
 */
import { DOWN } from '@wordpress/keycodes';

/**
 * Internal dependencies
 */
import { StudentActionMenu } from './index';

describe( '<StudentActionMenu />', () => {
	it( 'Should display the default options', () => {
		const {
			container: { firstChild: dropdownMenuContainer },
			getByText,
		} = render( <StudentActionMenu courseId="123" /> );
		const button = dropdownMenuContainer.querySelector(
			'.components-dropdown-menu__toggle'
		);

		// Open the dropdown menu.
		button.focus();
		fireEvent.keyDown( button, {
			keyCode: DOWN,
			preventDefault: () => {},
		} );

		expect( getByText( 'Add to Course' ) ).toBeTruthy();
		expect( getByText( 'Remove from Course' ) ).toBeTruthy();
		expect( getByText( 'Grading' ) ).toBeTruthy();
	} );
} );
